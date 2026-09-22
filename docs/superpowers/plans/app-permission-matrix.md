# App Permission Matrix

This is the working permission map for the app-centered rebuild. It should be verified by tests before release.

## Roles

| Role | Meaning | Scope |
| --- | --- | --- |
| `super_admin` | Full system administrator | All records |
| `service_leader` | Service-level leader | Managed service groups |
| `family_leader` | Family/service-group leader | Own `service_group_id` |
| `servant` | Field servant | Assigned beneficiaries plus service-group scoped servant flows |

## Resource Visibility

| Resource | Super Admin | Service Leader | Family Leader | Servant | Source |
| --- | --- | --- | --- | --- | --- |
| Dashboard | All system overview | Managed groups | Own group | Personal/field view | `routes/app.php`, `routes/servant.php` |
| Beneficiaries | All | Managed groups | Own group | Assigned and group-scoped | `WebAppScope::beneficiaries()` |
| Visits | All visible through beneficiary scope | Managed groups | Own group | Group or created/participating, depending surface | `WebAppScope::visits()`, `VisitPolicy` |
| Scheduled Visits | All | Managed groups | Own group | Assigned | `WebAppScope::scheduledVisits()` |
| Prayer Requests | All through beneficiary scope | Managed groups | Own group | Beneficiary-scoped or created, depending surface | `WebAppScope::prayerRequests()`, `PrayerRequestResource` |
| Medical Files | All through beneficiary scope | Managed groups | Own group | Beneficiary-scoped | `WebAppScope::medicalFiles()` |
| Users | All | Managed groups | Own group | Self only | `WebAppScope::users()` |
| Service Groups | All | Managed groups | Own group | Own group | `WebAppScope::serviceGroups()` |
| Reports | Broad access with role-specific data | Management reports | Management reports | Beneficiary reports only | `ReportController` |
| Notifications | Own notifications | Own notifications | Own notifications | Own notifications | `NotificationsBell`, notification services |
| Audit Logs | Policy controlled | Policy controlled | Likely restricted | Likely restricted | `AuditLogPolicy`, navigation `can()` |

## Verification Tests To Add

- [ ] Each role can access only the expected Web App routes.
- [ ] Each role sees scoped beneficiaries consistently in Web App, Filament, reports, and exports.
- [ ] Servants cannot read or mark another user's notifications.
- [ ] Servants cannot export management reports unless explicitly allowed.
- [ ] Family leaders cannot assign users or beneficiaries outside their group.
- [ ] Service leaders cannot manage groups outside `managedServiceGroupIds()`.
- [ ] Audit logs are visible only to authorized roles.

## Open Policy Questions

- Should servants see all visits in their service group, or only visits they created/participated in?
- Should servants access medical files from a direct main screen, beneficiary detail, or both?
- Should service leaders see audit logs for only managed groups, or all operational logs?
- Should Filament be available to service leaders after the Web App redesign, or only to super admins?

