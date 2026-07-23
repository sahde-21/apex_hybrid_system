# G3 Manual QA Matrix

Extend [`G2_MANUAL_QA_CHECKLIST.md`](G2_MANUAL_QA_CHECKLIST.md) with release-certification sign-off.

## Automated coverage (Sprint G3)

| Area | Automated evidence |
|------|-------------------|
| Security / permissions | `SprintG3SecurityAuditTest`, `AuthorizationHardeningTest`, `SuperAdminAuthorizationTest` |
| Workflows | `SalesDocumentWorkflowTest`, `PurchaseDocumentWorkflowTest`, `WorkflowEngineTest` |
| Accounting | `DoubleEntryEngineTest`, `FinancialCloseMasterDataTest` |
| API | `tests/Feature/Api/V1/*` |
| Intelligence | `IntelligencePhaseTest` (G1 permissions) |
| UI / errors | `SprintG2UiTest` |
| Deployment | `DeploymentPhaseTest`, `scf:release-readiness` |

## Role sign-off

| Role | Tester | Date | Pass |
|------|--------|------|------|
| Super Admin | Release Board (automated) | 2026-07-23 | ☑ automated |
| Manager | Release Board (automated) | 2026-07-23 | ☑ automated |
| Accountant | Release Board (automated) | 2026-07-23 | ☑ automated |
| Sales | Release Board (automated) | 2026-07-23 | ☑ automated |
| Warehouse | Release Board (automated) | 2026-07-23 | ☑ automated |
| HR | Release Board (automated) | 2026-07-23 | ☑ automated |
| Read-only | Release Board (automated) | 2026-07-23 | ☑ automated |

**Part C note:** Automated permission/workflow certification signed by Release Board. Customer staging **visual** sign-off remains Condition C1 before unrestricted production go-live (see `G4_1_PART_C_GO_NO_GO_CERTIFICATION.md`).

## Critical paths (manual spot-check)

1. Login → dashboard → logout  
2. Create customer → quotation → convert → invoice → payment (as sales)  
3. Purchase request → PO → bill (as purchasing)  
4. Product stock adjustment (as warehouse)  
5. Journal entry post (as accountant)  
6. Intelligence executive + export (as manager)  
7. 403 / 404 error pages (unauthorized URL)  
8. API token with insufficient ability (Postman/curl)  

## Locales

- [ ] EN / AR / CKB spot-check on dashboard + one form + one error page

## Release gate (ops)

- [ ] `php artisan migrate --force`  
- [ ] `php artisan scf:deploy-check`  
- [ ] `php artisan scf:release-readiness`  
- [ ] Queue worker running (production)  
- [ ] Scheduler cron configured (production)  
