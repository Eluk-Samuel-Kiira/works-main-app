<script>
// ============================================================
// CONFIG
// ============================================================
const JOBS_API = '/api/v1/job-posts';
const CATEGORIES_API = '/job-categories/with-counts';  // Changed - removed /api prefix
const COUNTRIES_API = '/job-countries/with-counts';    // Changed - removed /api prefix
const WEB_URL = '{{ config("api.web_app.url", "https://stardenaworks.com") }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let currentJobs = [];

// ============================================================
// HELPERS
// ============================================================
function toast(msg, type = 'success') {
    const alert = `<div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index:9999; font-size:12px; padding:8px 12px;" role="alert">${msg}<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>`;
    document.body.insertAdjacentHTML('beforeend', alert);
    setTimeout(() => document.querySelector('.alert')?.remove(), 3000);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = date.toLocaleString('en-GB', { month: 'short' });
    const year = date.getFullYear();
    
    const suffix = ['th', 'st', 'nd', 'rd'][(day % 10) > 3 ? 0 : ((day % 100) - (day % 10) !== 10 ? day % 10 : 0)];
    const formattedDay = day + suffix;
    
    return `${formattedDay} ${month} ${year}`;
}

function formatWhatsAppMessage(job, index) {
    const jobTitle = job.job_title || 'Job Opportunity';
    const companyName = job.company?.name || 'Company';
    const location = job.duty_station || job.job_location?.district || job.job_location?.country || 'Uganda';
    const jobUrl = `${WEB_URL}/jobs/${job.slug}`;
    const deadline = job.deadline ? formatDate(job.deadline) : 'Not specified';
    
    return `${jobTitle} at ${companyName}\n📍 ${location}\n\nApply: ${jobUrl}\nDeadline: ${deadline}`;
}

// ============================================================
// LOAD FILTERS
// ============================================================
async function loadFilters() {
    // console.log('Loading filters...');
    
    try {
        // Load categories with counts
        // console.log('Fetching categories from:', CATEGORIES_API);
        const categoriesRes = await fetch(CATEGORIES_API, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const categoriesData = await categoriesRes.json();
        // console.log('Categories response:', categoriesData);
        
        if (categoriesData.success && categoriesData.data) {
            const categoriesWithJobs = categoriesData.data;
            
            if (categoriesWithJobs.length > 0) {
                const categoryOptions = categoriesWithJobs.map(c => 
                    `<option value="${c.id}">${escapeHtml(c.name)} (${c.job_count} jobs)</option>`
                ).join('');
                document.getElementById('filterCategory').innerHTML = '<option value="">All Categories</option>' + categoryOptions;
            } else {
                document.getElementById('filterCategory').innerHTML = '<option value="">No categories with jobs</option>';
            }
        } else {
            console.error('Categories API returned error:', categoriesData);
            document.getElementById('filterCategory').innerHTML = '<option value="">Error loading categories</option>';
        }

        // Load countries with counts
        // console.log('Fetching countries from:', COUNTRIES_API);
        const countriesRes = await fetch(COUNTRIES_API, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const countriesData = await countriesRes.json();
        // console.log('Countries response:', countriesData);
        
        if (countriesData.success && countriesData.data) {
            const countriesWithJobs = countriesData.data;
            
            if (countriesWithJobs.length > 0) {
                const countryOptions = countriesWithJobs.map(c => 
                    `<option value="${escapeHtml(c.country)}">${escapeHtml(c.country)} (${c.job_count} jobs)</option>`
                ).join('');
                document.getElementById('filterCountry').innerHTML = '<option value="">All Countries</option>' + countryOptions;
            } else {
                document.getElementById('filterCountry').innerHTML = '<option value="">No countries with jobs</option>';
            }
        } else {
            console.error('Countries API returned error:', countriesData);
            document.getElementById('filterCountry').innerHTML = '<option value="">Error loading countries</option>';
        }

    } catch (e) {
        console.error('Failed to load filters:', e);
        document.getElementById('filterCategory').innerHTML = '<option value="">Error: ' + e.message + '</option>';
        document.getElementById('filterCountry').innerHTML = '<option value="">Error: ' + e.message + '</option>';
        toast('Failed to load filters: ' + e.message, 'error');
    }
}

// ============================================================
// LOAD JOBS
// ============================================================
async function loadJobs() {
    const spinner = document.getElementById('loadingSpinner');
    const content = document.getElementById('generatedContent');
    const category = document.getElementById('filterCategory').value;
    const country = document.getElementById('filterCountry').value;
    const employmentType = document.getElementById('filterEmploymentType').value;
    const limit = document.getElementById('filterLimit').value;

    spinner.classList.remove('d-none');
    content.innerHTML = '';

    try {
        let url = `${JOBS_API}?per_page=${limit}&is_active=true&deadline_after=${new Date().toISOString().split('T')[0]}`;
        if (category) url += `&job_category_id=${category}`;
        if (employmentType) url += `&employment_type=${employmentType}`;
        
        // console.log('Fetching jobs from:', url);
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const result = await response.json();
        
        let jobs = result.data || [];
        
        if (country) {
            jobs = jobs.filter(job => {
                const jobCountry = job.job_location?.country || '';
                return jobCountry === country;
            });
        }
        
        currentJobs = jobs;
        
        // Update stats
        const uniqueCompanies = [...new Set(jobs.map(j => j.company?.id).filter(Boolean))];
        const uniqueLocations = [...new Set(jobs.map(j => j.job_location?.country).filter(Boolean))];
        const expiringCount = jobs.filter(j => {
            if (!j.deadline) return false;
            const daysLeft = Math.ceil((new Date(j.deadline) - new Date()) / (1000 * 60 * 60 * 24));
            return daysLeft <= 7 && daysLeft > 0;
        }).length;
        
        document.getElementById('totalJobs').textContent = jobs.length;
        document.getElementById('totalCompanies').textContent = uniqueCompanies.length;
        document.getElementById('totalLocations').textContent = uniqueLocations.length;
        document.getElementById('expiringSoon').textContent = expiringCount;
        
        if (jobs.length === 0) {
            content.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="ti ti-brand-whatsapp fs-2 mb-2 d-block opacity-25"></i>
                    <p class="small mb-2">No jobs found</p>
                    <button class="btn btn-outline-success btn-sm" onclick="resetFilters()">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                </div>`;
        } else {
            let html = '';
            jobs.forEach((job, index) => {
                const message = formatWhatsAppMessage(job, index);
                const escapedMessage = escapeHtml(message);
                html += `
                    <div class="whatsapp-message">
                        <div class="d-flex justify-content-between align-items-center job-header">
                            <span class="text-success fw-semibold small">Job #${index + 1}</span>
                            <button class="btn btn-sm btn-outline-success copy-job-btn" onclick="copySingleMessage(${index})">
                                <i class="ti ti-copy me-1"></i>Copy
                            </button>
                        </div>
                        <pre id="message-${index}" style="margin:0; white-space:pre-wrap; font-family:inherit; font-size:12px; line-height:1.5;">${escapedMessage}</pre>
                    </div>`;
            });
            html += `
                <div class="text-center mt-2 pt-1">
                    <button class="btn btn-success btn-sm" onclick="copyAllLinks()">
                        <i class="ti ti-copy me-1"></i>Copy All (${jobs.length})
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ms-1" onclick="downloadTextFile()">
                        <i class="ti ti-download me-1"></i>Download .txt
                    </button>
                </div>`;
            content.innerHTML = html;
        }
        
        toast(`Loaded ${jobs.length} jobs`, 'success');
        
    } catch (e) {
        console.error('Load jobs error:', e);
        content.innerHTML = `<div class="alert alert-danger m-2 small">Failed to load jobs: ${e.message}</div>`;
        toast('Failed to load jobs', 'error');
    } finally {
        spinner.classList.add('d-none');
    }
}

// ============================================================
// COPY FUNCTIONS
// ============================================================
function copySingleMessage(index) {
    const messageElement = document.getElementById(`message-${index}`);
    if (!messageElement) return;
    
    const text = messageElement.innerText;
    navigator.clipboard.writeText(text).then(() => {
        toast('Copied!', 'success');
    }).catch(() => {
        toast('Failed to copy', 'error');
    });
}

function copyAllLinks() {
    if (currentJobs.length === 0) {
        toast('No messages to copy', 'warning');
        return;
    }
    
    const allMessages = currentJobs.map((job, i) => formatWhatsAppMessage(job, i)).join('\n\n' + '─'.repeat(48) + '\n\n');
    
    navigator.clipboard.writeText(allMessages).then(() => {
        toast(`Copied ${currentJobs.length} messages!`, 'success');
        
        const btn = document.getElementById('copyAllBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check fs-5"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 2000);
    }).catch(() => {
        toast('Failed to copy', 'error');
    });
}

function downloadTextFile() {
    if (currentJobs.length === 0) {
        toast('No messages to download', 'warning');
        return;
    }
    
    const header = `WhatsApp Job Links - Generated on ${new Date().toLocaleString()}\n${'='.repeat(50)}\n\n`;
    const messages = currentJobs.map((job, i) => formatWhatsAppMessage(job, i)).join('\n\n' + '─'.repeat(40) + '\n\n');
    const footer = `\n\n${'='.repeat(50)}\nTotal Jobs: ${currentJobs.length}\nGenerated by Stardena Works`;
    const content = header + messages + footer;
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `whatsapp-jobs-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    toast('Downloaded!', 'success');
}

function resetFilters() {
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterCountry').value = '';
    document.getElementById('filterEmploymentType').value = '';
    document.getElementById('filterLimit').value = '25';
    loadJobs();
}

function updateSelectedCount() {
    const select = document.getElementById('filterCategory');
    const selectedOption = select.options[select.selectedIndex];
    const countSpan = document.getElementById('selectedCategoryCount');
    if (countSpan && selectedOption.value) {
        const match = selectedOption.text.match(/\((\d+) jobs\)/);
        if (match) {
            countSpan.textContent = `(${match[1]} jobs)`;
        }
    } else if (countSpan) {
        countSpan.textContent = '';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // console.log('DOM loaded, initializing...');
    loadFilters();
});
</script>