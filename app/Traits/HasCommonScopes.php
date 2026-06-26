<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasCommonScopes
{
    /**
     * Scope: Filter by organization
     */
    public function scopeForOrganization(Builder $query, $organizationId = null): Builder
    {
        $orgId = $organizationId ?? Auth::user()?->organization_id;
        return $query->where('organization_id', $orgId);
    }

    /**
     * Scope: Filter by multiple organizations
     */
    public function scopeForOrganizations(Builder $query, array $organizationIds): Builder
    {
        return $query->whereIn('organization_id', $organizationIds);
    }

    /**
     * Scope: Only active records
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only inactive records
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by multiple statuses
     */
    public function scopeStatuses(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate, $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [$startDate, $endDate]);
    }

    /**
     * Scope: Records from today
     */
    public function scopeToday(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereDate($column, today());
    }

    /**
     * Scope: Records from this week
     */
    public function scopeThisWeek(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: Records from this month
     */
    public function scopeThisMonth(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereYear($column, now()->year)
            ->whereMonth($column, now()->month);
    }

    /**
     * Scope: Records from this year
     */
    public function scopeThisYear(Builder $query, $column = 'created_at'): Builder
    {
        return $query->whereYear($column, now()->year);
    }

    /**
     * Scope: Records from last N days
     */
    public function scopeLastDays(Builder $query, $days, $column = 'created_at'): Builder
    {
        return $query->where($column, '>=', now()->subDays($days));
    }

    /**
     * Scope: Search in multiple columns
     */
    public function scopeSearch(Builder $query, $search, array $columns): Builder
    {
        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Scope: Order by latest
     */
    public function scopeLatest(Builder $query, $column = 'created_at'): Builder
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Scope: Order by oldest
     */
    public function scopeOldest(Builder $query, $column = 'created_at'): Builder
    {
        return $query->orderBy($column, 'asc');
    }

    /**
     * Scope: Filter by standard (class)
     */
    public function scopeForStandard(Builder $query, $standardId): Builder
    {
        return $query->where('standard_id', $standardId);
    }

    /**
     * Scope: Filter by section
     */
    public function scopeForSection(Builder $query, $sectionId): Builder
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope: Filter by standard and section
     */
    public function scopeForClass(Builder $query, $standardId, $sectionId = null): Builder
    {
        $query->where('standard_id', $standardId);
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        return $query;
    }

    /**
     * Scope: Filter by academic year
     */
    public function scopeForAcademicYear(Builder $query, $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope: Current academic year
     */
    public function scopeCurrentAcademicYear(Builder $query): Builder
    {
        $currentYear = now()->month >= 4 
            ? now()->year . '-' . (now()->year + 1)
            : (now()->year - 1) . '-' . now()->year;
            
        return $query->where('academic_year', $currentYear);
    }

    /**
     * Scope: Soft deleted records only
     */
    public function scopeOnlyTrashed(Builder $query): Builder
    {
        if (method_exists($this, 'trashed')) {
            return $query->onlyTrashed();
        }
        return $query;
    }

    /**
     * Scope: Include soft deleted records
     */
    public function scopeWithTrashed(Builder $query): Builder
    {
        if (method_exists($this, 'trashed')) {
            return $query->withTrashed();
        }
        return $query;
    }

    /**
     * Scope: Filter by created by user
     */
    public function scopeCreatedBy(Builder $query, $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Filter by updated by user
     */
    public function scopeUpdatedBy(Builder $query, $userId): Builder
    {
        return $query->where('updated_by', $userId);
    }

    /**
     * Scope: Records created by current user
     */
    public function scopeMyRecords(Builder $query): Builder
    {
        return $query->where('created_by', Auth::id());
    }

    /**
     * Scope: Random order
     */
    public function scopeRandom(Builder $query, $limit = null): Builder
    {
        $query->inRandomOrder();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query;
    }

    /**
     * Scope: Paginate with custom per page
     */
    public function scopePaginateCustom(Builder $query, $perPage = 15)
    {
        return $query->paginate($perPage);
    }

    // NOTE: scopeWhereNull / scopeWhereNotNull were removed on purpose.
    // A scope named after a real builder method shadows it: Eloquent's
    // __call resolves named scopes BEFORE forwarding to the query builder,
    // so `$query->whereNotNull()` inside the scope re-invoked the scope
    // itself — infinite recursion → memory exhaustion → 500 on every code
    // path touching it (student save died in generateRollNumber, plus admit
    // cards, seating plans, exam copies…). The built-in whereNull /
    // whereNotNull do exactly what the wrappers did, so call sites work
    // unchanged. Never add a scope whose name matches a builder method.

    /**
     * Scope: Filter by type
     */
    public function scopeOfType(Builder $query, $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by multiple types
     */
    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        return $query->whereIn('type', $types);
    }

    /**
     * Scope: Filter by role (for User model)
     */
    public function scopeRole(Builder $query, $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Filter by multiple roles
     */
    public function scopeRoles(Builder $query, array $roles): Builder
    {
        return $query->whereIn('role', $roles);
    }

    /**
     * Scope: Has relation
     */
    public function scopeHasRelation(Builder $query, $relation): Builder
    {
        return $query->has($relation);
    }

    /**
     * Scope: Doesn't have relation
     */
    public function scopeDoesntHaveRelation(Builder $query, $relation): Builder
    {
        return $query->doesntHave($relation);
    }

    /**
     * Scope: Filter where column contains value
     */
    public function scopeContains(Builder $query, $column, $value): Builder
    {
        return $query->where($column, 'LIKE', "%{$value}%");
    }

    /**
     * Scope: Filter where column starts with value
     */
    public function scopeStartsWith(Builder $query, $column, $value): Builder
    {
        return $query->where($column, 'LIKE', "{$value}%");
    }

    /**
     * Scope: Filter where column ends with value
     */
    public function scopeEndsWith(Builder $query, $column, $value): Builder
    {
        return $query->where($column, 'LIKE', "%{$value}");
    }

    /**
     * Scope: Exclude specific IDs
     */
    public function scopeExcept(Builder $query, $ids): Builder
    {
        $ids = is_array($ids) ? $ids : [$ids];
        return $query->whereNotIn('id', $ids);
    }

    /**
     * Scope: Only specific IDs
     */
    public function scopeOnly(Builder $query, $ids): Builder
    {
        $ids = is_array($ids) ? $ids : [$ids];
        return $query->whereIn('id', $ids);
    }
}