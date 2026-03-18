# Product Overview

This is a **church/ministry beneficiary management system** — an internal admin tool for managing people with disabilities and their care.

## Core Purpose

Track beneficiaries (people receiving care), organize them into service groups, log visits, manage medical records, and coordinate between servants (volunteers) and leaders.

## Key Entities

- **Beneficiaries** — people receiving care; have medical, financial, and family profiles
- **Service Groups** — organizational units, each with a family leader and service leader
- **Visits** — logged interactions with beneficiaries; can be flagged as critical
- **Scheduled Visits** — planned future visits
- **Users** — staff/volunteers with role-based access
- **Ministry Notifications** — internal announcements
- **Audit Logs** — full change history for all key models

## User Roles (hierarchical)

- `super_admin` — full access
- `service_leader` — manages multiple service groups
- `family_leader` — manages one service group
- `servant` — sees only their assigned beneficiaries

## Localization

The UI supports Arabic (`ar`) and English (`en`). Arabic is the default. All user-facing strings go through Laravel's `__()` translation helper.
