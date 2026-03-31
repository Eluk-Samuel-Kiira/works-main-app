# Stardena Works Architecture & Patterns

## Tech Stack
- **Backend**: Laravel 12.53.0 with PostgreSQL
- **Frontend**: Bootstrap 5 + Tabler Components + Alpine.js
- **API**: RESTful pattern at `/api/v1/*`
- **Database**: PostgreSQL with proper indexing and foreign keys

## Current CRUD Pattern
1. **Models** (app/Models/Job/): Use HasFactory, slug generation, relationships
2. **API Controllers** (app/Http/Controllers/Api/Jobs/): RESTful CRUD with ApiResponse trait
3. **Request Classes** (app/Http/Requests/Api/Jobs/): Request validation
4. **Views** (resources/views/jobs/): Bootstrap modals with embedded JavaScript
5. **AJAX**: Uses `apiFetch()` utility with CSRF tokens, error handling, loading states

## Key CRUD Files Pattern
- `CompanyController.php`: API controller with store/update/destroy/show
- `resources/views/jobs/company/index.blade.php`: Main view with modals
- `resources/views/jobs/company/index-js.blade.php`: JavaScript logic
- Uses `CompanyRequest` for validation

## Important Details
- Company model has `getLogoUrlAttribute()` for asset/storage URL handling
- Industry/Company models generate slugs automatically in boot()
- Tables use proper indexing on is_active, slug, foreign keys
- API Controllers use pagination with per_page parameter
