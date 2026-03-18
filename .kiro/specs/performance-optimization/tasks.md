# Implementation Plan: Performance Optimization

## Overview

This plan implements comprehensive performance optimization for the Laravel 12 + Filament 4 beneficiary management system. The implementation focuses on eliminating N+1 queries through eager loading, adding database indexes, implementing caching strategies, and optimizing reports and exports. All optimizations maintain backward compatibility and require no UI changes.

## Tasks

- [x] 1. Create performance optimization service classes
  - [x] 1.1 Create EagerLoadingService with methods for all resource contexts
    - Implement beneficiariesTable(), beneficiariesTableWithAggregations(), beneficiaryInfolist()
    - Implement visitsTable(), scheduledVisitsTable(), serviceGroupsTable()
    - Implement singleBeneficiaryPdf() with nested relationship loading
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 3.1, 3.2, 4.1, 4.2, 4.3, 6.4, 9.1, 9.2, 9.3_
  
  - [x] 1.2 Create CacheService with filter caching methods
    - Implement getServiceGroups(), getActiveServants(), getGovernorates() with TTL
    - Implement cache invalidation methods (invalidateServiceGroupCaches, invalidateUserCaches, invalidateAllFilterCaches)
    - Configure Redis as cache driver
    - _Requirements: 8.1, 8.2, 8.3, 8.6_
  
  - [x] 1.3 Create QueryMonitoringService for slow query detection
    - Implement enable() method with DB::listen() for queries exceeding 1000ms threshold
    - Implement logSlowQuery() with production/development log channel routing
    - Implement debug mode methods (enableDebugMode, getQueryCount, getQueries)
    - _Requirements: 13.1, 13.2, 13.3, 13.4_

- [x] 2. Add database indexes migration
  - [x] 2.1 Create migration for performance indexes
    - Add indexes to beneficiaries table (service_group_id, assigned_servant_id, status, governorate)
    - Add indexes to visits table (beneficiary_id, visit_date, created_by, composite on is_critical+critical_resolved_at)
    - Add indexes to scheduled_visits table (beneficiary_id, assigned_servant_id, scheduled_date)
    - Add indexes to service_groups table (leader_id, service_leader_id, is_active)
    - Add indexes to users table (service_group_id, role, is_active)
    - Implement down() method to drop all indexes
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

- [x] 3. Optimize Beneficiaries Resource
  - [x] 3.1 Update BeneficiaryResource::getEloquentQuery() with eager loading
    - Apply EagerLoadingService::beneficiariesTable() relationships
    - Add withMax('visits', 'visit_date') for last visit aggregation
    - Ensure role-based scoping (family_leader, servant) executes before eager loading
    - _Requirements: 1.1, 1.2, 1.3, 11.1, 11.2, 11.3, 11.4, 14.1_
  
  - [ ]* 3.2 Write property test for beneficiaries table query count
    - **Property 1: Beneficiaries Table Query Count Bound**
    - **Validates: Requirements 1.4, 1.5**
  
  - [ ]* 3.3 Write property test for beneficiaries eager loading
    - **Property 2: Beneficiaries Table Eager Loading**
    - **Validates: Requirements 1.1, 1.2**
  
  - [ ]* 3.4 Write property test for last visit aggregation
    - **Property 3: Beneficiaries Last Visit Aggregation**
    - **Validates: Requirements 1.3**
  
  - [x] 3.5 Update BeneficiaryResource view page with infolist eager loading
    - Apply EagerLoadingService::beneficiaryInfolist() in view page query
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 14.2_
  
  - [ ]* 3.6 Write property test for beneficiary infolist query count
    - **Property 24: Beneficiary Infolist Query Count Bound**
    - **Validates: Requirements 9.4**

- [x] 4. Optimize Visits Resource
  - [x] 4.1 Update VisitResource::getEloquentQuery() with eager loading
    - Apply EagerLoadingService::visitsTable() relationships
    - Include nested beneficiary.serviceGroup relationship
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 14.1_
  
  - [ ]* 4.2 Write property test for visits table query count
    - **Property 4: Visits Table Query Count Bound**
    - **Validates: Requirements 2.4**
  
  - [ ]* 4.3 Write property test for visits eager loading
    - **Property 5: Visits Table Eager Loading**
    - **Validates: Requirements 2.1, 2.2, 2.3**

- [x] 5. Optimize ScheduledVisits Resource
  - [x] 5.1 Update ScheduledVisitResource::getEloquentQuery() with eager loading
    - Apply EagerLoadingService::scheduledVisitsTable() relationships
    - _Requirements: 3.1, 3.2, 3.3, 14.1_
  
  - [ ]* 5.2 Write property test for scheduled visits table query count
    - **Property 7: Scheduled Visits Table Query Count Bound**
    - **Validates: Requirements 3.3**
  
  - [ ]* 5.3 Write property test for scheduled visits eager loading
    - **Property 8: Scheduled Visits Table Eager Loading**
    - **Validates: Requirements 3.1, 3.2**

- [x] 6. Optimize ServiceGroups Resource
  - [x] 6.1 Update ServiceGroupResource::getEloquentQuery() with eager loading and aggregations
    - Apply EagerLoadingService::serviceGroupsTable() relationships
    - Add withCount(['servants', 'beneficiaries']) for count columns
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 14.1, 15.1_
  
  - [ ]* 6.2 Write property test for service groups table query count
    - **Property 9: Service Groups Table Query Count Bound**
    - **Validates: Requirements 4.4**
  
  - [ ]* 6.3 Write property test for service groups count aggregation
    - **Property 11: Service Groups Count Aggregation**
    - **Validates: Requirements 4.3**

- [x] 7. Optimize Dashboard Widgets
  - [x] 7.1 Optimize StatsOverviewWidget queries
    - Ensure exactly 4 queries for 4 statistics (total beneficiaries, active, visits this month, scheduled visits)
    - Use single aggregated queries per statistic
    - _Requirements: 5.1_
  
  - [x] 7.2 Optimize CriticalCasesWidget with eager loading
    - Eager load beneficiary and createdBy relationships in widget query
    - _Requirements: 5.2_
  
  - [x] 7.3 Optimize UnvisitedWidget with aggregation
    - Use single query with subquery for last visit date calculation
    - Use whereDoesntHave() for never-visited beneficiaries with indexed columns
    - _Requirements: 5.3, 5.4, 15.3_
  
  - [x] 7.4 Optimize BirthdayWidget query filtering
    - Filter birthdays in database query before eager loading relationships
    - _Requirements: 5.5_
  
  - [x] 7.5 Optimize VisitsChartWidget query count
    - Limit to maximum 12 queries for 6-month chart (2 visit types × 6 months)
    - _Requirements: 5.6_
  
  - [ ]* 7.6 Write property test for stats widget query count
    - **Property 12: Stats Overview Widget Query Count**
    - **Validates: Requirements 5.1**
  
  - [ ]* 7.7 Write property test for unvisited widget optimization
    - **Property 14: Unvisited Widget Query Optimization**
    - **Validates: Requirements 5.3, 5.4**

- [x] 8. Optimize PDF Report Generation
  - [x] 8.1 Optimize ReportService::beneficiariesPdf() method
    - Eager load serviceGroup and assignedServant relationships before passing to view
    - Limit result set to maximum 1000 records
    - _Requirements: 6.1, 6.7_
  
  - [x] 8.2 Optimize ReportService::visitsPdf() method
    - Eager load beneficiary and createdBy relationships before passing to view
    - Limit result set to maximum 1000 records
    - _Requirements: 6.2, 6.7_
  
  - [x] 8.3 Optimize ReportService::unvisitedPdf() method
    - Use aggregation query for last visit date calculation
    - Limit result set to maximum 1000 records
    - _Requirements: 6.3, 6.7_
  
  - [x] 8.4 Optimize ReportService::singleBeneficiaryPdf() method
    - Apply EagerLoadingService::singleBeneficiaryPdf() for all relationships
    - Include medications (active only), visits (latest 10), medicalFiles, prayerRequests
    - _Requirements: 6.4_
  
  - [x] 8.5 Optimize ReportService::serviceGroupPdf() method
    - Avoid N+1 queries in servant statistics calculation using aggregation
    - _Requirements: 6.5_
  
  - [x] 8.6 Optimize ReportService::serviceGroupBeneficiariesPdf() method
    - Use aggregation for visit counts instead of calling visits() on each beneficiary
    - _Requirements: 6.6_
  
  - [ ]* 8.7 Write property test for PDF report eager loading
    - **Property 17: PDF Report Eager Loading**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6**
  
  - [ ]* 8.8 Write property test for PDF report size limit
    - **Property 18: PDF Report Size Limit**
    - **Validates: Requirements 6.7**

- [x] 9. Optimize Excel Exports
  - [x] 9.1 Optimize BeneficiariesExport::query() method
    - Eager load serviceGroup and assignedServant relationships in query() method
    - Implement chunking for exports exceeding 500 records
    - Ensure base query count does not exceed 3
    - _Requirements: 7.1, 7.3, 7.4_
  
  - [x] 9.2 Optimize VisitsExport::query() method
    - Eager load beneficiary.serviceGroup and createdBy relationships in query() method
    - Implement chunking for exports exceeding 500 records
    - Ensure base query count does not exceed 3
    - _Requirements: 7.2, 7.3, 7.4_
  
  - [ ]* 9.3 Write property test for Excel export eager loading
    - **Property 19: Excel Export Eager Loading**
    - **Validates: Requirements 7.1, 7.2**
  
  - [ ]* 9.4 Write property test for Excel export chunking
    - **Property 20: Excel Export Chunking**
    - **Validates: Requirements 7.3**

- [x] 10. Implement cache invalidation in model observers
  - [x] 10.1 Update ServiceGroupObserver with cache invalidation
    - Call CacheService::invalidateServiceGroupCaches() in updated() and deleted() methods
    - _Requirements: 8.4_
  
  - [x] 10.2 Update UserObserver with cache invalidation
    - Call CacheService::invalidateUserCaches() in updated() and deleted() methods
    - _Requirements: 8.5_
  
  - [ ]* 10.3 Write property test for cache invalidation
    - **Property 23: Cache Invalidation on Updates**
    - **Validates: Requirements 8.4, 8.5**

- [x] 11. Update filter implementations to use caching
  - [x] 11.1 Update service group filters to use CacheService::getServiceGroups()
    - Replace direct ServiceGroup queries in filters with cached version
    - _Requirements: 8.1_
  
  - [x] 11.2 Update servant filters to use CacheService::getActiveServants()
    - Replace direct User queries in filters with cached version
    - _Requirements: 8.2_
  
  - [x] 11.3 Update governorate filters to use CacheService::getGovernorates()
    - Replace direct Beneficiary distinct queries with cached version
    - _Requirements: 8.3_
  
  - [ ]* 11.4 Write property test for filter options caching
    - **Property 22: Filter Options Caching**
    - **Validates: Requirements 8.1, 8.2, 8.3**

- [x] 12. Enable query monitoring in production
  - [x] 12.1 Register QueryMonitoringService in AppServiceProvider
    - Call QueryMonitoringService::enable() in boot() method for production environment
    - Configure slow-queries log channel in config/logging.php
    - _Requirements: 13.1, 13.4_

- [x] 13. Checkpoint - Run migration and verify indexes
  - Run `php artisan migrate` to apply performance indexes
  - Verify all indexes exist using database inspection
  - Test that queries use indexes with EXPLAIN queries
  - Ensure all tests pass, ask the user if questions arise

- [x] 14. Verify pagination maintains optimizations
  - [x] 14.1 Test that paginated tables only load current page relationships
    - Verify eager loading applies only to current page records
    - _Requirements: 12.2, 14.1_
  
  - [x] 14.2 Test that pagination count queries are efficient
    - Verify total count doesn't load all records into memory
    - _Requirements: 12.3_
  
  - [ ]* 14.3 Write property test for pagination eager loading efficiency
    - **Property 30: Pagination Eager Loading Efficiency**
    - **Validates: Requirements 12.2**

- [x] 15. Final checkpoint - Performance validation
  - Test all optimized resources (Beneficiaries, Visits, ScheduledVisits, ServiceGroups)
  - Test all dashboard widgets load within performance bounds
  - Test PDF reports and Excel exports complete without timeouts
  - Verify query counts stay within specified bounds for each component
  - Check slow query logs for any queries exceeding 1000ms threshold
  - Ensure all tests pass, ask the user if questions arise

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- All optimizations maintain backward compatibility with existing functionality
- No UI changes are required - all optimizations are transparent to users
- Redis must be configured and running for caching to work
- Query monitoring in production requires slow-queries log channel configuration
