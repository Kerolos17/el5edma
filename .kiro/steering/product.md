# Product Overview

**نظام الخدمة** (Ministry Service System) is a web-based management platform for charitable/religious service organizations. It helps coordinate care delivery to beneficiaries (مخدومين) through organized service groups (أسر).

## Core Domain

- **Beneficiaries**: People receiving care — tracked with personal, medical, financial, and family data. Auto-assigned codes (SN-0001 format).
- **Visits**: Logged visits to beneficiaries, including critical case flagging and escalation.
- **Service Groups (أسر)**: Organizational units grouping beneficiaries under a family leader.
- **Scheduled Visits**: Planned future visits with reminder notifications.
- **Medical Files & Medications**: Health records and active medication tracking.
- **Prayer Requests**: Spiritual care tracking.
- **Notifications**: In-app + push (FCM) notifications for reminders and alerts.
- **Reports**: PDF and Excel exports for beneficiaries, visits, and service groups.

## User Roles

| Role | Arabic | Access Level |
|------|--------|-------------|
| `super_admin` | مدير النظام | Full access |
| `service_leader` | أمين الخدمة | Full access, manages all groups |
| `family_leader` | أمين الأسرة | Own service group only |
| `servant` | خادم | Read-only, own service group |

## Key Business Rules

- Servants cannot create or edit beneficiaries — read-only access.
- Family leaders and servants only see beneficiaries in their own service group.
- Unvisited beneficiaries (14+ days) trigger alerts to assigned servants and family leaders.
- Birthday reminders are sent 3 days in advance.
- Arabic is the primary language; English is supported as fallback.
