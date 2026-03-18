# Requirements Document

## Introduction

This document defines the requirements for comprehensive performance optimization of the Laravel + Filament church beneficiary management system. The system currently suffers from N+1 query problems, slow table rendering, inefficient report generation, and missing caching strategies. This optimization will improve response times, reduce database load, and enhance user experience across all modules.

## Glossary

- **Query_Optimizer**: The component responsible for analyzing and optimizing database queries
- **Eager_Loader**: The mechanism that preloads related data to prevent N+1 queries
- **Cache_Manager**: The system component managing cached data and invalidation
- **Table_Renderer**: The Filament component rendering data tables
- **Report_Generator**: The service generating PDF and Excel reports
- **Widget_Engine**: The dashboard component rendering statistics and data widgets
- **Resource_Query**: The base Eloquent query for Filament resources
- **N+1_Query**: A performance anti-pattern where N additional queries are executed for N records

## Requirements

### Requirement 1: Eliminate N+1 Queries in Beneficiaries Table

**User Story:** As a user viewing the beneficiaries list, I want the table to load quickly without executing hundreds of queries, so that I can browse beneficiaries efficiently.

#### Acceptance Criteria

1. WHEN the beneficiaries table is rendered, THE Eager_Loader SHALL preload serviceGroup and assignedServant relationships
2. WHEN the beneficiaries table is rendered, THE Eager_Loader SHALL preload createdBy relationship
3. WHEN the last_visit_date column is displayed, THE Query_Optimizer SHALL use a single aggregated query instead of N queries
4. THE Resource_Query SHALL execute no more than 5 database queries regardless of the number of beneficiaries displayed
5. WHEN filters are applied, THE Query_Optimizer SHALL maintain eager loading for filtered results

### Requirement 2: Eliminate N+1 Queries in Visits Table

**User Story:** As a user viewing the visits list, I want the table to load instantly, so that I can review visit history without delays.

#### Acceptance Criteria

1. WHEN the visits table is rendered, THE Eager_Loader SHALL preload beneficiary relationship
2. WHEN the visits table is rendered, THE Eager_Loader SHALL preload createdBy relationship
3. WHEN the visits table is rendered, THE Eager_Loader SHALL preload beneficiary.serviceGroup nested relationship
4. THE Resource_Query SHALL execute no more than 3 database queries for the visits table
5. WHEN visit actions are displayed, THE Query_Optimizer SHALL avoid additional queries for permission checks

### Requirement 3: Eliminate N+1 Queries in Scheduled Visits Table

**User Story:** As a user viewing scheduled visits, I want the table to render quickly, so that I can plan upcoming visits efficiently.

#### Acceptance Criteria

1. WHEN the scheduled visits table is rendered, THE Eager_Loader SHALL preload beneficiary relationship
2. WHEN the scheduled visits table is rendered, THE Eager_Loader SHALL preload assignedServant relationship
3. THE Resource_Query SHALL execute no more than 3 database queries for the scheduled visits table

### Requirement 4: Eliminate N+1 Queries in Service Groups Table

**User Story:** As an administrator viewing service groups, I want the table to load without performance degradation, so that I can manage groups effectively.

#### Acceptance Criteria

1. WHEN the service groups table is rendered, THE Eager_Loader SHALL preload leader relationship
2. WHEN the service groups table is rendered, THE Eager_Loader SHALL preload serviceLeader relationship
3. WHEN count columns are displayed, THE Query_Optimizer SHALL use withCount() for servants_count and beneficiaries_count
4. THE Resource_Query SHALL execute no more than 3 database queries for the service groups table

### Requirement 5: Optimize Dashboard Widgets Performance

**User Story:** As a user loading the dashboard, I want all widgets to load quickly, so that I can see critical information immediately.

#### Acceptance Criteria

1. WHEN StatsOverviewWidget is rendered, THE Query_Optimizer SHALL execute exactly 4 queries for the 4 statistics
2. WHEN CriticalCasesWidget is rendered, THE Eager_Loader SHALL preload beneficiary and createdBy relationships
3. WHEN UnvisitedWidget is rendered, THE Query_Optimizer SHALL use a single optimized query with subquery for last visit date
4. WHEN UnvisitedWidget displays last visit column, THE Query_Optimizer SHALL avoid N queries by using eager loading or aggregation
5. WHEN BirthdayWidget is rendered, THE Query_Optimizer SHALL filter birthdays in a single query before loading relationships
6. WHEN VisitsChartWidget is rendered, THE Query_Optimizer SHALL execute exactly 12 queries (6 months × 2 visit types) maximum

### Requirement 6: Optimize Report Generation Performance

**User Story:** As a user generating reports, I want PDF and Excel exports to complete quickly even with large datasets, so that I can download reports without timeouts.

#### Acceptance Criteria

1. WHEN beneficiariesPdf is generated, THE Eager_Loader SHALL preload serviceGroup and assignedServant relationships
2. WHEN visitsPdf is generated, THE Eager_Loader SHALL preload beneficiary and createdBy relationships
3. WHEN unvisitedPdf is generated, THE Query_Optimizer SHALL use an optimized query with aggregation for last visit date
4. WHEN singleBeneficiaryPdf is generated, THE Eager_Loader SHALL preload all required relationships in a single query
5. WHEN serviceGroupPdf is generated, THE Query_Optimizer SHALL avoid N+1 queries in servant statistics calculation
6. WHEN serviceGroupBeneficiariesPdf is generated, THE Query_Optimizer SHALL use aggregation instead of calling visits() on each beneficiary
7. THE Report_Generator SHALL limit result sets to prevent memory exhaustion (maximum 1000 records per report)

### Requirement 7: Optimize Excel Export Performance

**User Story:** As a user exporting data to Excel, I want exports to complete quickly without server timeouts, so that I can analyze data offline.

#### Acceptance Criteria

1. WHEN BeneficiariesExport is executed, THE Eager_Loader SHALL preload serviceGroup and assignedServant relationships
2. WHEN VisitsExport is executed, THE Eager_Loader SHALL preload beneficiary.serviceGroup and createdBy relationships
3. THE Query_Optimizer SHALL use chunking for exports exceeding 500 records
4. THE Query_Optimizer SHALL execute no more than 3 queries per export regardless of record count

### Requirement 8: Implement Caching for Frequently Accessed Data

**User Story:** As a user navigating the system, I want frequently accessed data to load instantly, so that I can work efficiently without waiting for repeated queries.

#### Acceptance Criteria

1. WHEN service groups are loaded in filters, THE Cache_Manager SHALL cache the service groups list for 1 hour
2. WHEN active servants are loaded in filters, THE Cache_Manager SHALL cache the servants list for 1 hour
3. WHEN governorate options are loaded, THE Cache_Manager SHALL cache distinct governorates for 24 hours
4. WHEN a service group is updated, THE Cache_Manager SHALL invalidate related caches
5. WHEN a user is updated, THE Cache_Manager SHALL invalidate related caches
6. THE Cache_Manager SHALL use Redis as the cache driver for optimal performance

### Requirement 9: Optimize Beneficiary Infolist Performance

**User Story:** As a user viewing a beneficiary's details, I want the page to load instantly with all related data, so that I can review complete information without delays.

#### Acceptance Criteria

1. WHEN a beneficiary infolist is rendered, THE Eager_Loader SHALL preload serviceGroup relationship
2. WHEN a beneficiary infolist is rendered, THE Eager_Loader SHALL preload assignedServant relationship
3. WHEN a beneficiary infolist is rendered, THE Eager_Loader SHALL preload createdBy relationship
4. THE Resource_Query SHALL execute no more than 2 database queries for the infolist view

### Requirement 10: Add Database Indexes for Performance

**User Story:** As a system administrator, I want database queries to execute quickly using proper indexes, so that the system remains responsive under load.

#### Acceptance Criteria

1. THE Query_Optimizer SHALL use an index on beneficiaries.service_group_id for filtering
2. THE Query_Optimizer SHALL use an index on beneficiaries.assigned_servant_id for filtering
3. THE Query_Optimizer SHALL use an index on beneficiaries.status for filtering
4. THE Query_Optimizer SHALL use an index on visits.beneficiary_id for relationship queries
5. THE Query_Optimizer SHALL use an index on visits.visit_date for date range queries
6. THE Query_Optimizer SHALL use an index on visits.created_by for filtering
7. THE Query_Optimizer SHALL use a composite index on visits(is_critical, critical_resolved_at) for critical cases queries
8. THE Query_Optimizer SHALL use an index on scheduled_visits.scheduled_date for date filtering

### Requirement 11: Optimize Resource Query Scoping

**User Story:** As a user with role-based access, I want my scoped queries to execute efficiently, so that I only see my data without performance penalties.

#### Acceptance Criteria

1. WHEN a family_leader accesses beneficiaries, THE Query_Optimizer SHALL use indexed service_group_id for scoping
2. WHEN a servant accesses beneficiaries, THE Query_Optimizer SHALL use indexed assigned_servant_id for scoping
3. WHEN role-based scoping is applied, THE Eager_Loader SHALL maintain all eager loading optimizations
4. THE Resource_Query SHALL execute scoping filters before eager loading to minimize loaded records

### Requirement 12: Implement Query Result Pagination

**User Story:** As a user viewing large datasets, I want tables to paginate efficiently, so that I can navigate through records without loading everything at once.

#### Acceptance Criteria

1. WHEN a table exceeds 50 records, THE Table_Renderer SHALL paginate results with a default page size of 25
2. WHEN pagination is applied, THE Query_Optimizer SHALL only load the current page's relationships
3. THE Table_Renderer SHALL display total record count without loading all records
4. WHEN a user changes pages, THE Query_Optimizer SHALL maintain all eager loading optimizations

### Requirement 13: Monitor and Log Query Performance

**User Story:** As a developer, I want to identify slow queries automatically, so that I can continuously improve system performance.

#### Acceptance Criteria

1. WHEN a query exceeds 1000ms execution time, THE Query_Optimizer SHALL log the query with execution time
2. WHEN N+1 queries are detected in development, THE Query_Optimizer SHALL log a warning with the source location
3. THE Query_Optimizer SHALL provide a debug mode that displays query counts per page
4. WHERE the environment is production, THE Query_Optimizer SHALL log slow queries to a dedicated log file

### Requirement 14: Optimize Filament Resource Loading

**User Story:** As a user navigating between resources, I want pages to load quickly, so that I can work efficiently across different modules.

#### Acceptance Criteria

1. WHEN a resource list page is loaded, THE Resource_Query SHALL apply eager loading in the getEloquentQuery() method
2. WHEN a resource view page is loaded, THE Resource_Query SHALL preload all relationships displayed in the infolist
3. WHEN a resource edit page is loaded, THE Resource_Query SHALL preload only relationships needed for the form
4. THE Resource_Query SHALL avoid loading relationships not displayed on the current page

### Requirement 15: Implement Efficient Counting Strategies

**User Story:** As a user viewing aggregate counts, I want counts to calculate efficiently, so that I can see statistics without delays.

#### Acceptance Criteria

1. WHEN displaying relationship counts, THE Query_Optimizer SHALL use withCount() instead of loading full relationships
2. WHEN calculating visit counts per beneficiary, THE Query_Optimizer SHALL use aggregation queries
3. WHEN displaying "never visited" beneficiaries, THE Query_Optimizer SHALL use whereDoesntHave() with proper indexing
4. THE Query_Optimizer SHALL avoid calling count() on already-loaded collections when withCount() is available
