<script>
// ============================================================
// CONFIG
// ============================================================
const JOBS_API = '/api/v1/job-posts';
const CATEGORIES_API = '/job-categories/with-counts';
const COUNTRIES_API = '/job-countries/with-counts';
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

// ============================================================
// DATE FILTER HELPER
// ============================================================
function getDateFilterRange(filterValue) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    switch(filterValue) {
        case 'today':
            return { start: today, end: new Date(today.getTime() + 86400000) };
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            return { start: yesterday, end: today };
        case '2days':
            const twoDaysAgo = new Date(today);
            twoDaysAgo.setDate(twoDaysAgo.getDate() - 2);
            return { start: twoDaysAgo, end: new Date(twoDaysAgo.getTime() + 86400000) };
        case '3days':
            const threeDaysAgo = new Date(today);
            threeDaysAgo.setDate(threeDaysAgo.getDate() - 3);
            return { start: threeDaysAgo, end: new Date(threeDaysAgo.getTime() + 86400000) };
        case 'this_week':
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            return { start: weekStart, end: new Date() };
        default:
            return null;
    }
}

// ============================================================
// LOAD FILTERS
// ============================================================
async function loadFilters() {
    try {
        // Load categories with counts
        const categoriesRes = await fetch(CATEGORIES_API, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const categoriesData = await categoriesRes.json();
        
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
        }

        // Load countries with counts
        const countriesRes = await fetch(COUNTRIES_API, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const countriesData = await countriesRes.json();
        
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
        }

    } catch (e) {
        console.error('Failed to load filters:', e);
        toast('Failed to load filters: ' + e.message, 'error');
    }
}



// ============================================================
// CLEAN WHATSAPP MESSAGE FORMAT (Target Format)
// ============================================================
function formatWhatsAppMessage(job, index) {
    const jobTitle = job.job_title || 'Job Opportunity';
    const companyName = job.company?.name || 'Company';
    const jobUrl = `${WEB_URL}/jobs/${job.slug}`;
    const deadline = job.deadline ? formatDate(job.deadline) : 'Not specified';
    
    return `${jobTitle} job at ${companyName}\n${jobUrl}\nDeadline of this Job: "${deadline}"\n\n`;
}

// ============================================================
// RENDER BATCH CARDS WITH TARGET FORMAT
// ============================================================
function renderBatchCards() {
    const content = document.getElementById('generatedContent');
    
    // Group jobs into batches of 10
    const batchSize = 10;
    const batches = [];
    for (let i = 0; i < currentJobs.length; i += batchSize) {
        batches.push(currentJobs.slice(i, i + batchSize));
    }
    
    if (batches.length === 0) {
        content.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="ti ti-brand-whatsapp fs-2 mb-2 d-block opacity-25"></i>
                <p class="small mb-2">No jobs found</p>
                <button class="btn btn-outline-success btn-sm" onclick="resetFilters()">
                    <i class="ti ti-refresh me-1"></i>Reset
                </button>
            </div>`;
        return;
    }
    
    let html = '';
    
    batches.forEach((batch, batchIndex) => {
        const batchNumber = batchIndex + 1;
        const totalBatches = batches.length;
        const generatedDate = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        // Build batch message in target format
        let batchMessage = `JOBS SHARED ON ${generatedDate} PART ${batchNumber}\n\n`;
        
        batch.forEach((job, idx) => {
            const jobTitle = job.job_title || 'Job Opportunity';
            const companyName = job.company?.name || 'Company';
            const jobUrl = `${WEB_URL}/jobs/${job.slug}`;
            const deadline = job.deadline ? formatDate(job.deadline) : 'Not specified';
            
            batchMessage += `${jobTitle} job at ${companyName}\n`;
            batchMessage += `${jobUrl}\n`;
            batchMessage += `Deadline of this Job: "${deadline}"\n\n`;
        });
        
        html += `
            <div class="card whatsapp-card mb-4">
                <div class="card-header bg-gradient-success py-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="ti ti-brand-whatsapp fs-5 text-success me-2"></i>
                        <span class="badge bg-success me-2">Part ${batchNumber}/${totalBatches}</span>
                        <span class="badge bg-info">${batch.length} Jobs</span>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <button class="btn btn-sm btn-success copy-batch-btn me-1" onclick="copyBatch(${batchIndex})">
                            <i class="ti ti-copy me-1"></i>Copy Part ${batchNumber}
                        </button>
                        <button class="btn btn-sm btn-primary share-wa-btn" onclick="shareToWhatsApp(${batchIndex})">
                            <i class="ti ti-brand-whatsapp me-1"></i>Share to WhatsApp
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="whatsapp-preview-wrapper">
                        <div class="whatsapp-header">
                            <i class="ti ti-message-circle"></i> WhatsApp Preview
                        </div>
                        <pre id="batch-${batchIndex}" class="whatsapp-preview">${escapeHtml(batchMessage)}</pre>
                    </div>
                </div>
                <div class="card-footer bg-transparent py-2">
                    <small class="text-muted">
                        <i class="ti ti-info-circle me-1"></i> 
                        Click "Copy Part ${batchNumber}" to copy, or "Share to WhatsApp" to send directly
                    </small>
                </div>
            </div>
        `;
    });
    
    // Add summary footer
    html += `
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <div class="text-muted small">
                <i class="ti ti-chart-bar me-1"></i>
                Total: ${currentJobs.length} jobs in ${batches.length} parts
            </div>
            <div>
                <button class="btn btn-outline-success btn-sm" onclick="copyAllBatches()">
                    <i class="ti ti-copy me-1"></i> Copy All Parts
                </button>
                <button class="btn btn-outline-secondary btn-sm ms-2" onclick="downloadTextFile()">
                    <i class="ti ti-download me-1"></i> Download .txt
                </button>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
}

// ============================================================
// COPY SINGLE BATCH
// ============================================================
function copyBatch(batchIndex) {
    const messageElement = document.getElementById(`batch-${batchIndex}`);
    if (!messageElement) return;
    
    const text = messageElement.innerText;
    navigator.clipboard.writeText(text).then(() => {
        toast(`Part ${batchIndex + 1} copied!`, 'success');
    }).catch(() => {
        toast('Failed to copy', 'error');
    });
}

// ============================================================
// COPY ALL BATCHES
// ============================================================
function copyAllBatches() {
    if (currentJobs.length === 0) {
        toast('No jobs to copy', 'warning');
        return;
    }
    
    const batchSize = 10;
    const batches = [];
    for (let i = 0; i < currentJobs.length; i += batchSize) {
        batches.push(currentJobs.slice(i, i + batchSize));
    }
    
    let allMessages = '';
    const generatedDate = new Date().toISOString().slice(0, 19).replace('T', ' ');
    
    batches.forEach((batch, batchIndex) => {
        allMessages += `JOBS SHARED ON ${generatedDate} PART ${batchIndex + 1}\n\n`;
        
        batch.forEach((job) => {
            const jobTitle = job.job_title || 'Job Opportunity';
            const companyName = job.company?.name || 'Company';
            const jobUrl = `${WEB_URL}/jobs/${job.slug}`;
            const deadline = job.deadline ? formatDate(job.deadline) : 'Not specified';
            
            allMessages += `${jobTitle} job at ${companyName}\n`;
            allMessages += `${jobUrl}\n`;
            allMessages += `Deadline of this Job: "${deadline}"\n\n`;
        });
    });
    
    navigator.clipboard.writeText(allMessages).then(() => {
        toast(`Copied ${currentJobs.length} jobs in ${batches.length} parts!`, 'success');
    }).catch(() => {
        toast('Failed to copy', 'error');
    });
}

// ============================================================
// DOWNLOAD TEXT FILE
// ============================================================
function downloadTextFile() {
    if (currentJobs.length === 0) {
        toast('No jobs to download', 'warning');
        return;
    }
    
    const batchSize = 10;
    const batches = [];
    for (let i = 0; i < currentJobs.length; i += batchSize) {
        batches.push(currentJobs.slice(i, i + batchSize));
    }
    
    let allMessages = '';
    const generatedDate = new Date().toISOString().slice(0, 19).replace('T', ' ');
    
    batches.forEach((batch, batchIndex) => {
        allMessages += `JOBS SHARED ON ${generatedDate} PART ${batchIndex + 1}\n\n`;
        
        batch.forEach((job) => {
            const jobTitle = job.job_title || 'Job Opportunity';
            const companyName = job.company?.name || 'Company';
            const jobUrl = `${WEB_URL}/jobs/${job.slug}`;
            const deadline = job.deadline ? formatDate(job.deadline) : 'Not specified';
            
            allMessages += `${jobTitle} job at ${companyName}\n`;
            allMessages += `${jobUrl}\n`;
            allMessages += `Deadline of this Job: "${deadline}"\n\n`;
        });
    });
    
    const blob = new Blob([allMessages], { type: 'text/plain' });
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

// ============================================================
// SHARE TO WHATSAPP
// ============================================================
function shareToWhatsApp(batchIndex) {
    const messageElement = document.getElementById(`batch-${batchIndex}`);
    if (!messageElement) return;
    
    const text = messageElement.innerText;
    const encodedText = encodeURIComponent(text);
    const whatsappUrl = `https://wa.me/?text=${encodedText}`;
    window.open(whatsappUrl, '_blank');
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
    const dateRange = document.getElementById('filterDateRange')?.value || '';
    const limit = document.getElementById('filterLimit')?.value || 50;

    spinner.classList.remove('d-none');
    content.innerHTML = '';

    try {
        let url = `${JOBS_API}?per_page=${limit}&is_active=true&deadline_after=${new Date().toISOString().split('T')[0]}`;
        if (category) url += `&job_category_id=${category}`;
        if (employmentType) url += `&employment_type=${employmentType}`;
        
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const result = await response.json();
        
        let jobs = result.data || [];
        
        // Apply country filter
        if (country) {
            jobs = jobs.filter(job => {
                const jobCountry = job.job_location?.country || '';
                return jobCountry === country;
            });
        }
        
        // Apply date range filter
        if (dateRange) {
            const dateFilter = getDateFilterRange(dateRange);
            if (dateFilter) {
                jobs = jobs.filter(job => {
                    if (!job.created_at) return false;
                    const createdDate = new Date(job.created_at);
                    return createdDate >= dateFilter.start && createdDate < dateFilter.end;
                });
            }
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
                    <p class="small mb-2">No jobs found with selected filters</p>
                    <button class="btn btn-outline-success btn-sm" onclick="resetFilters()">
                        <i class="ti ti-refresh me-1"></i>Reset Filters
                    </button>
                </div>`;
        } else {
            renderBatchCards();
        }
        
        toast(`Loaded ${jobs.length} jobs in ${Math.ceil(jobs.length / 10)} batches`, 'success');
        
    } catch (e) {
        console.error('Load jobs error:', e);
        content.innerHTML = `<div class="alert alert-danger m-2 small">Failed to load jobs: ${e.message}</div>`;
        toast('Failed to load jobs', 'error');
    } finally {
        spinner.classList.add('d-none');
    }
}

// ============================================================
// RESET FUNCTION
// ============================================================
function resetFilters() {
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterCountry').value = '';
    document.getElementById('filterEmploymentType').value = '';
    document.getElementById('filterDateRange').value = '';
    loadJobs();
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
    loadFilters();
});
</script>