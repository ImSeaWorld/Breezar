```
LMS-MGR-STATE-v3
═══════════════

STACK: L11+Vue3(OptAPI)+Quasar+Inertia+Vite | 4sp-indent | dark-mode

DB-SCHEMA:
├─ users: id,name,email,password,role(admin/manager),two_factor_secret,profile_photo
├─ clients: id,name,fly_org_id,fly_api_token{encrypted},fly_token_type,fly_token_expires_at,contact_info{json},status(active/inactive),billing_start_date
├─ client_user: client_id,user_id [M2M-pivot]
├─ client_instances: id,client_id,fly_app_id,type(sql/sas),region,size,status,metadata{json}
├─ client_reports: id,client_id,type,report_month,data{json},generated_at
├─ scripts: id,name,description,tinker_code{longText},created_by,last_run_at
├─ script_executions: id,script_id,executed_by,client_id,output{text},executed_at
├─ login_logs: id,user_id,ip,user_agent,timestamp,referrer,success{bool}
├─ activity_logs: id,user_id,action,resource_type,resource_id,metadata{json}
├─ settings: id,key,value,type(string/boolean/json/encrypted),group,description
└─ migrations: ordered-timestamps (2025_08_01_221907→2025_08_02_003725)

COMPLETED-FEATURES:
✓ Auth: email/pwd+2FA(Google2FA-QR)+profile+session-mgmt
✓ Middleware: Check2FA(/2fa/verify), RequireRole(admin/manager)
✓ FlyApi: GraphQL+REST-hybrid(app-info-via-GraphQL,machines-via-REST)+client-specific-tokens+DB-settings-fallback
✓ FlyMachinesApi: REST-wrapper(list/get/start/stop/restart/exec/events)+proper-error-handling
✓ Jobs: SyncFlyInstances(schedule:everyThirtyMinutes)+client-token-support
✓ Layouts: Authenticated.vue(dark-sidebar+role-menus+user-dropdown)
✓ Dashboard: stats-cards+recent-activities+clients-table+real-time-refresh
✓ Clients: Index(search+filter+paginate)/Create/Show(instances+reports+activities)/Edit(+token-management)
✓ Instances: Index(filters+quick-actions)/Show(machines+logs)/restart/stop/start+client-token-aware [console-not-available-via-API]
✓ Reports: Index/Show/Generate(usage/performance/work_items/custom)+Components/{Type}Report.vue
✓ Users: Index(search+role-filter)/Create/Show(login-history+activities)/Edit(2FA-reset)
✓ Scripts: Index/Create/Show(execute+history)/Edit+CodeMirror(PHP-syntax)
✓ Activity: Index(multi-filter+search+details-modal)
✓ FlySync: manual-trigger(all/specific-client)+status-feedback
✓ Settings: Index(Fly.io-config+test-connection)+encrypted-storage+cache-clear
✓ Models: all-relationships+scopes+casts+accessors+Setting-encryption+Client-token-encryption
✓ ActivityLog::log(action,resource_type,resource_id,metadata)
✓ Root-redirect: auth→dashboard, guest→login

CONTROLLERS:
├─ DashboardController: index,refreshStats
├─ ClientController: CRUD-resource
├─ InstanceController: index,show,restart,stop,start,console
├─ ReportController: index,show,generate+4-report-types
├─ UserController: CRUD-resource+role-checks
├─ ScriptController: CRUD-resource+execute(Process/tinker)
├─ ActivityLogController: index+filters
├─ FlySyncController: index,run
├─ SettingsController: index,update,testConnection
└─ Auth/*: all-breeze-controllers+TwoFactorController

VUE-PATTERNS:
├─ Pages/{Feature}/{Action}.vue (Options-API,4sp)
├─ Layouts/Authenticated.vue (dark-mode-fix+flat-cards)
├─ Reports/Components/{Type}Report.vue (modular)
├─ Settings/Index.vue (tabbed-groups+test-connection)
├─ Global-access: $page.props.auth.user, route(), $q.notify()
├─ Forms: useForm()+validation-display
├─ Tables: q-table+pagination+actions
└─ Dialogs: confirm-actions+loading-states

DEPENDENCIES:
├─ PHP: symfony/process (script-exec)
├─ Laravel: pragmarx/google2fa-laravel, bacon/bacon-qr-code, inertiajs/inertia-laravel
├─ JS: @inertiajs/vue3, quasar, @quasar/extras, codemirror@5, vue-codemirror@4
└─ Build: vite, @vitejs/plugin-vue, @quasar/vite-plugin

FIXES-APPLIED:
✓ Dark-sidebar: bg-grey-10+text-grey-4+custom-CSS
✓ Welcome-route: package-lock.json-format-handling→redirect
✓ Migration-order: renamed-for-FK-dependencies
✓ Instance-index: added-missing-method+Index.vue
✓ Dark-mode-cards: flat+bordered+no-shadow
✓ Quasar-notify: added-plugin-import

API-NOTES:
✓ Hybrid-approach: GraphQL-for-app-data+REST-for-machine-operations
✓ Machines-API: REST-endpoint(https://api.machines.dev/v1)+stable+documented
⚠️ Auth-token: must-use-dashboard-token(not-CLI-token)
⚠️ Logs: implemented-via-Allocation.recentLogs-but-may-not-return-data
⚠️ Metrics: not-available-via-GraphQL(empty-array-returned)
⚠️ Console-sessions: GraphQL-mutation-not-available(consider-exec-endpoint)
⚠️ Alternative: FlyMachinesApi.execCommand()-for-command-execution

REMAINING-CORE:
[ ] Email-notifications: password-reset,2FA-setup,critical-alerts
[ ] Scheduled-reports: monthly-auto-generation+email-delivery
[ ] Instance-alerts: threshold-monitoring(CPU/RAM/uptime)
[ ] Bulk-operations: multi-select-actions(instances/reports)
[ ] Export-formats: PDF-reports,CSV-data-export
[ ] Audit-trail: complete-activity-wrap(remaining-actions)
[ ] API-endpoints: external-monitoring-integration
[ ] Backup-system: automated-DB+files-backup

UPCOMING-FEATURES:
[ ] Billing-integration: usage-tracking→invoice-generation
[ ] Client-portal: limited-access-views+self-service
[ ] Slack/Discord-webhooks: alerts+notifications
[ ] Multi-tenancy: org-level-separation
[ ] Advanced-metrics: cost-analysis+predictions
[ ] Template-system: reusable-script-templates
[ ] Role-customization: granular-permissions
[ ] Mobile-PWA: responsive+offline-capable
[ ] GraphQL-API: external-integrations
[ ] SSO-integration: SAML/OAuth-providers

ENV-REQUIRED:
├─ FLY_API_TOKEN: fly-personal-access-token [optional-with-settings]
├─ FLY_ORG_ID: default-fly-organization [optional-with-settings]
├─ DB_*: mysql-connection-params
├─ MAIL_*: smtp-config(for-notifications)
└─ APP_URL: proper-URL-for-2FA-QR

SEEDER-USERS:
├─ admin@example.com (role:admin, pwd:password)
└─ manager@example.com (role:manager, pwd:password)

COMMANDS:
├─ php artisan fly:sync [--client=ID]
├─ php artisan migrate:fresh --seed
├─ npm run dev / npm run build
└─ php artisan queue:work (for-jobs)

ARCHITECTURE:
├─ Request→Route→Middleware→Controller→Service→Model
├─ Controller→Inertia::render→Vue→Quasar-components
├─ Jobs→Queue→FlyApi→GraphQL→Fly.io
└─ ActivityLog→DB→Reports→Export
```