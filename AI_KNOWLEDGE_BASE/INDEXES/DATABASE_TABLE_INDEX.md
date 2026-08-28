# Database Table Index

**52 tables**, reconstructed from 110 migrations.

Columns are the **union of every `create`/`table` migration** touching the table, so a column added
and later dropped may still appear. The `Migrations` count is the audit trail — and `DESCRIBE` is
the only authority before you write a migration against a column.

| ID | Table | Columns | Created in | Migrations |
|---|---|---|---|---|
| `TABLE-001` | `admin_settings` | 3 | `2025_07_02_072409_create_admin_settings_table.php` | 3 |
| `TABLE-002` | `allowed_tests` | 2 | `2025_06_23_115716_create_allowed_tests_table.php` | 2 |
| `TABLE-003` | `cache` | 3 | `0001_01_01_000001_create_cache_table.php` | 2 |
| `TABLE-004` | `cache_locks` | 3 | `0001_01_01_000001_create_cache_table.php` | 2 |
| `TABLE-005` | `compliances` | 2 | `2025_06_23_093258_create_compliances_table.php` | 2 |
| `TABLE-006` | `credit_consume` | 4 | `2026_05_04_090222_create_credit_consume_table.php` | 2 |
| `TABLE-007` | `credits` | 10 | `2025_10_15_123918_create_credits_table.php` | 6 |
| `TABLE-008` | `discount_code_price_tiers` | 2 | `2026_04_17_000001_rebuild_discount_codes_system.php` | 3 |
| `TABLE-009` | `discount_code_user` | 2 | `2025_06_26_092920_create_discount_code_user_table.php` | 3 |
| `TABLE-010` | `discount_code_users` | 2 | `2026_04_17_000001_rebuild_discount_codes_system.php` | 3 |
| `TABLE-011` | `discount_codes` | 25 | `2026_04_17_000001_rebuild_discount_codes_system.php` | 13 |
| `TABLE-012` | `email_template` | 6 | `2026_03_09_090021_create_email_template_table.php` | 2 |
| `TABLE-013` | `failed_jobs` | 6 | `0001_01_01_000002_create_jobs_table.php` | 2 |
| `TABLE-014` | `job_batches` | 10 | `0001_01_01_000002_create_jobs_table.php` | 2 |
| `TABLE-015` | `jobs` | 6 | `0001_01_01_000002_create_jobs_table.php` | 2 |
| `TABLE-016` | `lms_delivery_queue` | 16 | `2026_05_08_000004_create_lms_delivery_queue_table.php` | 5 |
| `TABLE-017` | `lms_delivery_tokens` | 5 | `2026_05_08_000003_create_lms_delivery_tokens_table.php` | 2 |
| `TABLE-018` | `lms_provider_configs` | 5 | `2026_05_08_000001_create_lms_provider_configs_table.php` | 2 |
| `TABLE-019` | `lms_sessions` | 14 | `2026_05_08_000002_create_lms_sessions_table.php` | 2 |
| `TABLE-020` | `organization_configs` | 10 | `2026_03_29_054559_create_organization_configs_table.php` | 4 |
| `TABLE-021` | `organization_patient_sessions` | 9 | `2026_03_24_124245_create_organization_patient_sessions_table.php` | 2 |
| `TABLE-022` | `organization_settings_options` | 1 | `2026_04_28_092714_create_organization_settings_options_table.php` | 2 |
| `TABLE-023` | `organization_types` | 5 | `2025_06_19_104359_create_organization_types_table.php` | 2 |
| `TABLE-024` | `organizations` | 33 | `2025_06_19_104358_create_organizations.php` | 5 |
| `TABLE-025` | `password_reset_tokens` | 3 | `0001_01_01_000001_create_users_table.php` | 2 |
| `TABLE-026` | `patient_tests` | 21 | `2025_06_23_131210_create_patient_tests_table.php` | 20 |
| `TABLE-027` | `patients` | 17 | `2025_06_23_085520_create_patients_table.php` | 6 |
| `TABLE-028` | `personal_access_tokens` | 5 | `2025_06_09_101232_create_personal_access_tokens_table.php` | 2 |
| `TABLE-029` | `price_details` | 3 | `2025_06_24_100030_create_price_details_table.php` | 2 |
| `TABLE-030` | `pricing_audit_logs` | 6 | `2026_01_27_112600_create_pricing_audit_logs_table.php` | 2 |
| `TABLE-031` | `privileges` | 2 | `2025_06_23_103451_create_privileges_table.php` | 2 |
| `TABLE-032` | `prolific_ids` | 5 | `2026_03_29_054643_create_prolific_ids_table.php` | 2 |
| `TABLE-033` | `restricted_ips` | 1 | `2025_06_18_064142_restricted_ip.php` | 2 |
| `TABLE-034` | `sessions` | 6 | `0001_01_01_000001_create_users_table.php` | 2 |
| `TABLE-035` | `test_conditions` | 4 | `2025_06_20_071025_create_test_conditions_table.php` | 2 |
| `TABLE-036` | `test_email_templates` | 0 | _altered only_ | 3 |
| `TABLE-037` | `test_invitations` | 9 | `2026_02_26_084221_create_test_invitations_table.php` | 6 |
| `TABLE-038` | `test_resume_tokens` | 5 | `2026_05_25_000002_create_test_resume_tokens_table.php` | 2 |
| `TABLE-039` | `test_section_plates` | 6 | `2025_06_20_071402_create_test_section_plates_table.php` | 2 |
| `TABLE-040` | `test_sections` | 22 | `2025_06_20_071148_create_test_sections_table.php` | 8 |
| `TABLE-041` | `test_sessions` | 6 | `2026_03_02_180000_create_test_sessions_table.php` | 4 |
| `TABLE-042` | `testanswers` | 17 | `2025_06_20_070717_create_test_answers_table.php` | 16 |
| `TABLE-043` | `tests` | 8 | `2025_06_20_070605_create_test_table_table.php` | 6 |
| `TABLE-044` | `transaction_details` | 12 | `2025_10_15_123744_create_transaction_details_table.php` | 8 |
| `TABLE-045` | `transactions` | 9 | `2025_07_08_082801_create_transactions_table.php` | 6 |
| `TABLE-046` | `user_assigned_tests` | 2 | `2026_04_24_000001_create_user_assigned_tests_table.php` | 2 |
| `TABLE-047` | `user_email_settings` | 4 | `2025_10_15_124114_create_user_email_settings_table.php` | 3 |
| `TABLE-048` | `user_email_templates` | 0 | _altered only_ | 3 |
| `TABLE-049` | `user_emails` | 4 | `2025_07_02_070723_create_user_emails_table.php` | 3 |
| `TABLE-050` | `user_hidden_tests` | 2 | `2026_04_01_122406_create_user_hidden_tests_table.php` | 3 |
| `TABLE-051` | `user_stripe_details` | 3 | `2026_02_12_145901_create_user_stripe_details_table.php` | 2 |
| `TABLE-052` | `users` | 35 | `0001_01_01_000001_create_users_table.php` | 24 |

---

## Column detail

### `admin_settings` — `TABLE-001`

| Column | Type | Defined in |
|---|---|---|
| `test_email_subject` | string | `2025_07_02_072409_create_admin_settings_table.php` |
| `test_email_body` | longText | `2025_07_02_072409_create_admin_settings_table.php` |
| `type` | string | `2025_07_02_072409_create_admin_settings_table.php` |

### `allowed_tests` — `TABLE-002`

| Column | Type | Defined in |
|---|---|---|
| `test` | string | `2025_06_23_115716_create_allowed_tests_table.php` |
| `active` | boolean | `2025_06_23_115716_create_allowed_tests_table.php` |

### `cache` — `TABLE-003`

| Column | Type | Defined in |
|---|---|---|
| `key` | string | `0001_01_01_000001_create_cache_table.php` |
| `value` | mediumText | `0001_01_01_000001_create_cache_table.php` |
| `expiration` | integer | `0001_01_01_000001_create_cache_table.php` |

### `cache_locks` — `TABLE-004`

| Column | Type | Defined in |
|---|---|---|
| `key` | string | `0001_01_01_000001_create_cache_table.php` |
| `owner` | string | `0001_01_01_000001_create_cache_table.php` |
| `expiration` | integer | `0001_01_01_000001_create_cache_table.php` |

### `compliances` — `TABLE-005`

| Column | Type | Defined in |
|---|---|---|
| `compliance` | string | `2025_06_23_093258_create_compliances_table.php` |
| `active` | boolean | `2025_06_23_093258_create_compliances_table.php` |

### `credit_consume` — `TABLE-006`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | unsignedBigInteger | `2026_05_04_090222_create_credit_consume_table.php` |
| `credits_used` | integer | `2026_05_04_090222_create_credit_consume_table.php` |
| `event_type` | string | `2026_05_04_090222_create_credit_consume_table.php` |
| `ref_id` | json | `2026_05_04_090222_create_credit_consume_table.php` |

### `credits` — `TABLE-007`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | unsignedBigInteger | `2025_10_15_123918_create_credits_table.php` |
| `credits` | integer | `2025_10_15_123918_create_credits_table.php` |
| `expiry_date` | date | `2025_10_15_123918_create_credits_table.php` |
| `price_per_credit` | float | `2025_10_15_123918_create_credits_table.php` |
| `total_price` | float | `2025_10_15_123918_create_credits_table.php` |
| `has_expiry` | boolean | `2025_10_15_123918_create_credits_table.php` |
| `is_unlimited_credit` | boolean | `2025_10_15_123918_create_credits_table.php` |
| `coupon_code` | string | `2025_10_15_123918_create_credits_table.php` |
| `source` | unsignedTinyInteger | `2026_02_26_110941_add_columns_to_credits_table.php` |
| `credited_by` | unsignedBigInteger | `2026_02_26_110941_add_columns_to_credits_table.php` |

### `discount_code_price_tiers` — `TABLE-008`

| Column | Type | Defined in |
|---|---|---|
| `discount_code_id` | foreignId | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `price_detail_id` | foreignId | `2026_04_17_000001_rebuild_discount_codes_system.php` |

### `discount_code_user` — `TABLE-009`

| Column | Type | Defined in |
|---|---|---|
| `discount_code_id` | foreignId | `2025_06_26_092920_create_discount_code_user_table.php` |
| `user_id` | foreignId | `2025_06_26_092920_create_discount_code_user_table.php` |

### `discount_code_users` — `TABLE-010`

| Column | Type | Defined in |
|---|---|---|
| `discount_code_id` | foreignId | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `user_id` | foreignId | `2026_04_17_000001_rebuild_discount_codes_system.php` |

### `discount_codes` — `TABLE-011`

| Column | Type | Defined in |
|---|---|---|
| `code` | string | `2025_06_26_092636_create_discount_code_table.php` |
| `type` | string | `2025_06_26_092636_create_discount_code_table.php` |
| `minimum_applicable_cost` | decimal | `2025_06_26_092636_create_discount_code_table.php` |
| `fixed_discount` | decimal | `2025_06_26_092636_create_discount_code_table.php` |
| `percentage_discount` | decimal | `2025_06_26_092636_create_discount_code_table.php` |
| `plan_id` | integer | `2025_06_26_092636_create_discount_code_table.php` |
| `one_time_code` | boolean | `2025_06_26_092636_create_discount_code_table.php` |
| `one_per_email` | boolean | `2025_06_26_092636_create_discount_code_table.php` |
| `expiry_date` | timestamp | `2025_06_26_092636_create_discount_code_table.php` |
| `usage` | integer | `2025_10_15_064715_add_usage_to_discount_codes_table.php` |
| `plan_id` | json | `2025_12_03_104431_change_plan_id_to_json_in_discount_codes_table.php` |
| `description` | string | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `type` | enum | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `value` | decimal | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `minimum_order_amount` | decimal | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `max_uses` | unsignedInteger | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `max_uses_per_user` | unsignedInteger | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `is_active` | boolean | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `starts_at` | timestamp | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `expires_at` | timestamp | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `created_by` | foreignId | `2026_04_17_000001_rebuild_discount_codes_system.php` |
| `max_uses_per_user` | integer | `2026_04_23_135313_modify_max_uses_per_user_nullable_on_discount_codes_table.php` |

_Dropped later by a migration (may still be listed above): `usage`._

### `email_template` — `TABLE-012`

| Column | Type | Defined in |
|---|---|---|
| `name` | string | `2026_03_09_090021_create_email_template_table.php` |
| `subject` | string | `2026_03_09_090021_create_email_template_table.php` |
| `header` | text | `2026_03_09_090021_create_email_template_table.php` |
| `body` | longText | `2026_03_09_090021_create_email_template_table.php` |
| `footer` | text | `2026_03_09_090021_create_email_template_table.php` |
| `status` | enum | `2026_03_09_090021_create_email_template_table.php` |

### `failed_jobs` — `TABLE-013`

| Column | Type | Defined in |
|---|---|---|
| `uuid` | string | `0001_01_01_000002_create_jobs_table.php` |
| `connection` | text | `0001_01_01_000002_create_jobs_table.php` |
| `queue` | text | `0001_01_01_000002_create_jobs_table.php` |
| `payload` | longText | `0001_01_01_000002_create_jobs_table.php` |
| `exception` | longText | `0001_01_01_000002_create_jobs_table.php` |
| `failed_at` | timestamp | `0001_01_01_000002_create_jobs_table.php` |

### `job_batches` — `TABLE-014`

| Column | Type | Defined in |
|---|---|---|
| `id` | string | `0001_01_01_000002_create_jobs_table.php` |
| `name` | string | `0001_01_01_000002_create_jobs_table.php` |
| `total_jobs` | integer | `0001_01_01_000002_create_jobs_table.php` |
| `pending_jobs` | integer | `0001_01_01_000002_create_jobs_table.php` |
| `failed_jobs` | integer | `0001_01_01_000002_create_jobs_table.php` |
| `failed_job_ids` | longText | `0001_01_01_000002_create_jobs_table.php` |
| `options` | mediumText | `0001_01_01_000002_create_jobs_table.php` |
| `cancelled_at` | integer | `0001_01_01_000002_create_jobs_table.php` |
| `created_at` | integer | `0001_01_01_000002_create_jobs_table.php` |
| `finished_at` | integer | `0001_01_01_000002_create_jobs_table.php` |

### `jobs` — `TABLE-015`

| Column | Type | Defined in |
|---|---|---|
| `queue` | string | `0001_01_01_000002_create_jobs_table.php` |
| `payload` | longText | `0001_01_01_000002_create_jobs_table.php` |
| `attempts` | unsignedTinyInteger | `0001_01_01_000002_create_jobs_table.php` |
| `reserved_at` | unsignedInteger | `0001_01_01_000002_create_jobs_table.php` |
| `available_at` | unsignedInteger | `0001_01_01_000002_create_jobs_table.php` |
| `created_at` | unsignedInteger | `0001_01_01_000002_create_jobs_table.php` |

### `lms_delivery_queue` — `TABLE-016`

| Column | Type | Defined in |
|---|---|---|
| `id` | uuid | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `lms_session_id` | uuid | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `idempotency_key` | string | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `event_type` | enum | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `provider_type` | string | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `payload` | json | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `status` | enum | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `attempt_count` | unsignedSmallInteger | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `max_attempts` | unsignedSmallInteger | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `next_retry_at` | timestamp | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `delivered_at` | timestamp | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `provider_ref_id` | string | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `provider_response` | json | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `error_log` | json | `2026_05_08_000004_create_lms_delivery_queue_table.php` |
| `context` | json | `2026_05_11_000001_add_context_to_lms_delivery_queue_table.php` |
| `event_type` | string | `2026_05_11_000002_add_section_progress_to_lms_delivery_queue_event_type.php` |

_Dropped later by a migration (may still be listed above): `context`._

### `lms_delivery_tokens` — `TABLE-017`

| Column | Type | Defined in |
|---|---|---|
| `lms_provider_config_id` | unsignedBigInteger | `2026_05_08_000003_create_lms_delivery_tokens_table.php` |
| `access_token` | text | `2026_05_08_000003_create_lms_delivery_tokens_table.php` |
| `token_type` | string | `2026_05_08_000003_create_lms_delivery_tokens_table.php` |
| `expires_at` | timestamp | `2026_05_08_000003_create_lms_delivery_tokens_table.php` |
| `scopes` | json | `2026_05_08_000003_create_lms_delivery_tokens_table.php` |

### `lms_provider_configs` — `TABLE-018`

| Column | Type | Defined in |
|---|---|---|
| `org_id` | unsignedBigInteger | `2026_05_08_000001_create_lms_provider_configs_table.php` |
| `provider_type` | enum | `2026_05_08_000001_create_lms_provider_configs_table.php` |
| `is_active` | boolean | `2026_05_08_000001_create_lms_provider_configs_table.php` |
| `config` | text | `2026_05_08_000001_create_lms_provider_configs_table.php` |
| `signing_key` | string | `2026_05_08_000001_create_lms_provider_configs_table.php` |

### `lms_sessions` — `TABLE-019`

| Column | Type | Defined in |
|---|---|---|
| `id` | uuid | `2026_05_08_000002_create_lms_sessions_table.php` |
| `org_id` | unsignedBigInteger | `2026_05_08_000002_create_lms_sessions_table.php` |
| `lms_provider_config_id` | unsignedBigInteger | `2026_05_08_000002_create_lms_sessions_table.php` |
| `patient_id` | unsignedBigInteger | `2026_05_08_000002_create_lms_sessions_table.php` |
| `unique_test_id` | string | `2026_05_08_000002_create_lms_sessions_table.php` |
| `session_token` | string | `2026_05_08_000002_create_lms_sessions_table.php` |
| `launch_nonce` | string | `2026_05_08_000002_create_lms_sessions_table.php` |
| `nonce_consumed_at` | timestamp | `2026_05_08_000002_create_lms_sessions_table.php` |
| `lms_context` | json | `2026_05_08_000002_create_lms_sessions_table.php` |
| `status` | enum | `2026_05_08_000002_create_lms_sessions_table.php` |
| `token_expires_at` | dateTime | `2026_05_08_000002_create_lms_sessions_table.php` |
| `test_completed_at` | timestamp | `2026_05_08_000002_create_lms_sessions_table.php` |
| `reported_at` | timestamp | `2026_05_08_000002_create_lms_sessions_table.php` |
| `ip_address` | string | `2026_05_08_000002_create_lms_sessions_table.php` |

### `organization_configs` — `TABLE-020`

| Column | Type | Defined in |
|---|---|---|
| `organization_id` | unsignedBigInteger | `2026_03_29_054559_create_organization_configs_table.php` |
| `form_type` | enum | `2026_03_29_054559_create_organization_configs_table.php` |
| `consent_page` | string | `2026_03_29_054559_create_organization_configs_table.php` |
| `validate_prolific_id` | boolean | `2026_03_29_054559_create_organization_configs_table.php` |
| `redirect_url` | string | `2026_03_29_054559_create_organization_configs_table.php` |
| `is_healthstream` | boolean | `2026_03_29_054559_create_organization_configs_table.php` |
| `is_active` | boolean | `2026_03_29_054559_create_organization_configs_table.php` |
| `fields` | json | `2026_03_29_054559_create_organization_configs_table.php` |
| `notes` | text | `2026_03_29_054559_create_organization_configs_table.php` |
| `logo` | string | `2026_04_30_000001_add_logo_to_organization_configs_table.php` |

_Dropped later by a migration (may still be listed above): `logo`._

### `organization_patient_sessions` — `TABLE-021`

| Column | Type | Defined in |
|---|---|---|
| `org_id` | foreignId | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `patient_id` | foreignId | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `test_id` | string | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `token` | string | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `signature` | string | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `AICC_URL` | text | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `AICC_SID` | string | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `status` | enum | `2026_03_24_124245_create_organization_patient_sessions_table.php` |
| `expires_at` | timestamp | `2026_03_24_124245_create_organization_patient_sessions_table.php` |

### `organization_settings_options` — `TABLE-022`

| Column | Type | Defined in |
|---|---|---|
| `name` | string | `2026_04_28_092714_create_organization_settings_options_table.php` |

### `organization_types` — `TABLE-023`

| Column | Type | Defined in |
|---|---|---|
| `name` | string | `2025_06_19_104359_create_organization_types_table.php` |
| `created_by` | unsignedBigInteger | `2025_06_19_104359_create_organization_types_table.php` |
| `updated_by` | unsignedBigInteger | `2025_06_19_104359_create_organization_types_table.php` |
| `active` | boolean | `2025_06_19_104359_create_organization_types_table.php` |
| `is_deleted` | boolean | `2025_06_19_104359_create_organization_types_table.php` |

### `organizations` — `TABLE-024`

| Column | Type | Defined in |
|---|---|---|
| `organization_type_id` | unsignedBigInteger | `2025_06_19_104358_create_organizations.php` |
| `user_id` | unsignedBigInteger | `2025_06_19_104358_create_organizations.php` |
| `organization_name` | string | `2025_06_19_104358_create_organizations.php` |
| `website_url` | string | `2025_06_19_104358_create_organizations.php` |
| `test_url` | string | `2025_06_19_104358_create_organizations.php` |
| `compliance_id` | unsignedBigInteger | `2025_06_19_104358_create_organizations.php` |
| `static_ip` | string | `2025_06_19_104358_create_organizations.php` |
| `middle_name` | string | `2025_06_19_104358_create_organizations.php` |
| `registration_fee_paid` | decimal | `2025_06_19_104358_create_organizations.php` |
| `privileges` | json | `2025_06_19_104358_create_organizations.php` |
| `allowed_tests` | json | `2025_06_19_104358_create_organizations.php` |
| `show_tcv_branding` | boolean | `2025_06_19_104358_create_organizations.php` |
| `anonymize_patient` | boolean | `2025_06_19_104358_create_organizations.php` |
| `show_occupational_questions` | boolean | `2025_06_19_104358_create_organizations.php` |
| `show_gender` | boolean | `2025_06_19_104358_create_organizations.php` |
| `show_zip` | boolean | `2025_06_19_104358_create_organizations.php` |
| `show_default_test_condition` | boolean | `2025_06_19_104358_create_organizations.php` |
| `show_patient_id` | boolean | `2025_06_19_104358_create_organizations.php` |
| `send_test_email_to_patients` | boolean | `2025_06_19_104358_create_organizations.php` |
| `run_test_on_subdomain` | boolean | `2025_06_19_104358_create_organizations.php` |
| `authorized_redirect` | boolean | `2025_06_19_104358_create_organizations.php` |
| `logo_uploaded` | boolean | `2025_06_19_104358_create_organizations.php` |
| `api_key` | string | `2025_06_19_104358_create_organizations.php` |
| `cornerstone_client_id` | string | `2025_06_19_104358_create_organizations.php` |
| `cornerstone_client_secret` | string | `2025_06_19_104358_create_organizations.php` |
| `cornerstone_token` | string | `2025_06_19_104358_create_organizations.php` |
| `allowed_redirect` | tinyInteger | `2025_10_15_122456_add_multiple_fields_in_organizations_table.php` |
| `subdomain_url` | string | `2025_10_15_122456_add_multiple_fields_in_organizations_table.php` |
| `allowed_redirect_url` | string | `2025_10_15_122456_add_multiple_fields_in_organizations_table.php` |
| `logo_url` | string | `2025_10_15_122456_add_multiple_fields_in_organizations_table.php` |

### `password_reset_tokens` — `TABLE-025`

| Column | Type | Defined in |
|---|---|---|
| `email` | string | `0001_01_01_000001_create_users_table.php` |
| `token` | string | `0001_01_01_000001_create_users_table.php` |
| `created_at` | timestamp | `0001_01_01_000001_create_users_table.php` |

### `patient_tests` — `TABLE-026`

| Column | Type | Defined in |
|---|---|---|
| `unique_test_id` | string | `2025_06_23_131210_create_patient_tests_table.php` |
| `patient_id` | foreignId | `2025_06_23_131210_create_patient_tests_table.php` |
| `test_id` | foreignId | `2025_06_23_131210_create_patient_tests_table.php` |
| `status` | enum | `2025_06_23_131210_create_patient_tests_table.php` |
| `eye_tested` | string | `2025_07_11_055718_add_eye_tested_and_condition_to_patient_tests_table.php` |
| `condition` | string | `2025_07_11_055718_add_eye_tested_and_condition_to_patient_tests_table.php` |
| `condition` | unsignedTinyInteger | `2026_02_18_124957_change_condition_type_in_patienttests_table.php` |
| `parent_test_id` | string | `2026_02_19_000445_add_parent_test_id_and_update_status_on_patienttests_table.php` |
| `result_json` | json | `2026_03_26_185558_add_result_json_to_patient_tests_table.php` |
| `result_generated_at` | timestamp | `2026_03_26_185558_add_result_json_to_patient_tests_table.php` |
| `is_email_invite` | boolean | `2026_04_22_000001_add_is_email_invite_to_patient_tests_table.php` |
| `ip_address` | string | `2026_04_23_000001_add_ip_address_to_patient_tests_table.php` |
| `occupation_purpose` | tinyInteger | `2026_04_29_000001_add_occupation_fields_to_patient_tests_table.php` |
| `occupation_organization` | string | `2026_04_29_000001_add_occupation_fields_to_patient_tests_table.php` |
| `occupation_contact_person` | string | `2026_04_29_000001_add_occupation_fields_to_patient_tests_table.php` |
| `occupation_contact_email` | string | `2026_04_29_000001_add_occupation_fields_to_patient_tests_table.php` |
| `test_invitation_id` | unsignedBigInteger | `2026_05_05_000001_add_test_invitation_id_to_patient_tests_table.php` |
| `resend_count` | unsignedInteger | `2026_05_06_140922_add_resend_count_to_patient_tests_table.php` |

_Dropped later by a migration (may still be listed above): `eye_tested`, `condition`, `parent_test_id`, `is_email_invite`, `ip_address`, `test_invitation_id`, `resend_count`._

### `patients` — `TABLE-027`

| Column | Type | Defined in |
|---|---|---|
| `first_name` | string | `2025_06_23_085520_create_patients_table.php` |
| `last_name` | string | `2025_06_23_085520_create_patients_table.php` |
| `dob` | string | `2025_06_23_085520_create_patients_table.php` |
| `user_id` | foreignId | `2025_06_23_085520_create_patients_table.php` |
| `patient_id` | string | `2025_06_23_085520_create_patients_table.php` |
| `email` | string | `2025_06_23_085520_create_patients_table.php` |
| `zipcode` | string | `2025_06_23_085520_create_patients_table.php` |
| `test_condition` | tinyInteger | `2025_06_23_085520_create_patients_table.php` |
| `gender` | tinyInteger | `2025_06_23_085520_create_patients_table.php` |
| `test_eyes` | string | `2025_10_15_095040_add_fields_to_patients_table.php` |
| `org_sess` | string | `2025_10_15_095040_add_fields_to_patients_table.php` |
| `id_tcv_aicc_org_user` | integer | `2025_10_15_095040_add_fields_to_patients_table.php` |
| `identification` | string | `2025_10_15_095040_add_fields_to_patients_table.php` |
| `is_encrypted` | boolean | `2025_10_15_095040_add_fields_to_patients_table.php` |
| `csod_token` | string | `2025_10_15_095040_add_fields_to_patients_table.php` |

### `personal_access_tokens` — `TABLE-028`

| Column | Type | Defined in |
|---|---|---|
| `name` | string | `2025_06_09_101232_create_personal_access_tokens_table.php` |
| `token` | string | `2025_06_09_101232_create_personal_access_tokens_table.php` |
| `abilities` | text | `2025_06_09_101232_create_personal_access_tokens_table.php` |
| `last_used_at` | timestamp | `2025_06_09_101232_create_personal_access_tokens_table.php` |
| `expires_at` | timestamp | `2025_06_09_101232_create_personal_access_tokens_table.php` |

### `price_details` — `TABLE-029`

| Column | Type | Defined in |
|---|---|---|
| `from` | integer | `2025_06_24_100030_create_price_details_table.php` |
| `to` | integer | `2025_06_24_100030_create_price_details_table.php` |
| `price_per_credit` | decimal | `2025_06_24_100030_create_price_details_table.php` |

### `pricing_audit_logs` — `TABLE-030`

| Column | Type | Defined in |
|---|---|---|
| `action` | string | `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `pricing_id` | foreignId | `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `user_id` | foreignId | `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `old_values` | json | `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `new_values` | json | `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `created_at` | timestamp | `2026_01_27_112600_create_pricing_audit_logs_table.php` |

### `privileges` — `TABLE-031`

| Column | Type | Defined in |
|---|---|---|
| `privilege` | string | `2025_06_23_103451_create_privileges_table.php` |
| `active` | boolean | `2025_06_23_103451_create_privileges_table.php` |

### `prolific_ids` — `TABLE-032`

| Column | Type | Defined in |
|---|---|---|
| `organization_id` | unsignedBigInteger | `2026_03_29_054643_create_prolific_ids_table.php` |
| `prolific_id` | string | `2026_03_29_054643_create_prolific_ids_table.php` |
| `is_used` | boolean | `2026_03_29_054643_create_prolific_ids_table.php` |
| `used_at` | timestamp | `2026_03_29_054643_create_prolific_ids_table.php` |
| `patient_id` | unsignedBigInteger | `2026_03_29_054643_create_prolific_ids_table.php` |

### `restricted_ips` — `TABLE-033`

| Column | Type | Defined in |
|---|---|---|
| `ip_address` | string | `2025_06_18_064142_restricted_ip.php` |

### `sessions` — `TABLE-034`

| Column | Type | Defined in |
|---|---|---|
| `id` | string | `0001_01_01_000001_create_users_table.php` |
| `user_id` | foreignId | `0001_01_01_000001_create_users_table.php` |
| `ip_address` | string | `0001_01_01_000001_create_users_table.php` |
| `user_agent` | text | `0001_01_01_000001_create_users_table.php` |
| `payload` | longText | `0001_01_01_000001_create_users_table.php` |
| `last_activity` | integer | `0001_01_01_000001_create_users_table.php` |

### `test_conditions` — `TABLE-035`

| Column | Type | Defined in |
|---|---|---|
| `test_id` | foreignId | `2025_06_20_071025_create_test_conditions_table.php` |
| `cond_section` | string | `2025_06_20_071025_create_test_conditions_table.php` |
| `cond_status` | string | `2025_06_20_071025_create_test_conditions_table.php` |
| `cond_section_next` | string | `2025_06_20_071025_create_test_conditions_table.php` |

### `test_email_templates` — `TABLE-036`

_No columns detected (index/constraint-only migrations)._

### `test_invitations` — `TABLE-037`

| Column | Type | Defined in |
|---|---|---|
| `test_id` | foreignId | `2026_02_26_084221_create_test_invitations_table.php` |
| `user_id` | foreignId | `2026_02_26_084221_create_test_invitations_table.php` |
| `email` | string | `2026_02_26_084221_create_test_invitations_table.php` |
| `token` | string | `2026_02_26_084221_create_test_invitations_table.php` |
| `verification_code` | string | `2026_02_26_084221_create_test_invitations_table.php` |
| `is_used` | boolean | `2026_02_26_084221_create_test_invitations_table.php` |
| `expires_at` | timestamp | `2026_02_26_084221_create_test_invitations_table.php` |
| `is_revoked` | boolean | `2026_05_06_114739_add_is_revoked_to_test_invitations_table.php` |
| `resend_count` | unsignedInteger | `2026_05_06_122126_add_resend_count_to_test_invitations_table.php` |

_Dropped later by a migration (may still be listed above): `is_revoked`, `resend_count`._

### `test_resume_tokens` — `TABLE-038`

| Column | Type | Defined in |
|---|---|---|
| `unique_test_id` | string | `2026_05_25_000002_create_test_resume_tokens_table.php` |
| `test_invitation_id` | unsignedBigInteger | `2026_05_25_000002_create_test_resume_tokens_table.php` |
| `patient_id` | unsignedBigInteger | `2026_05_25_000002_create_test_resume_tokens_table.php` |
| `token` | string | `2026_05_25_000002_create_test_resume_tokens_table.php` |
| `expires_at` | timestamp | `2026_05_25_000002_create_test_resume_tokens_table.php` |

### `test_section_plates` — `TABLE-039`

| Column | Type | Defined in |
|---|---|---|
| `test_id` | foreignId | `2025_06_20_071402_create_test_section_plates_table.php` |
| `test_section_id` | integer | `2025_06_20_071402_create_test_section_plates_table.php` |
| `plate_image` | string | `2025_06_20_071402_create_test_section_plates_table.php` |
| `order` | integer | `2025_06_20_071402_create_test_section_plates_table.php` |
| `answer` | text | `2025_06_20_071402_create_test_section_plates_table.php` |
| `is_demo` | boolean | `2025_06_20_071402_create_test_section_plates_table.php` |

### `test_sections` — `TABLE-040`

| Column | Type | Defined in |
|---|---|---|
| `test_id` | foreignId | `2025_06_20_071148_create_test_sections_table.php` |
| `category` | tinyInteger | `2025_06_20_071148_create_test_sections_table.php` |
| `type` | string | `2025_06_20_071148_create_test_sections_table.php` |
| `plates_randomized` | boolean | `2025_06_20_071148_create_test_sections_table.php` |
| `adaptive` | boolean | `2025_06_20_071148_create_test_sections_table.php` |
| `plate_duration` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `answer_duration` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `miss_plates` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `miss_plates_in_row` | boolean | `2025_06_20_071148_create_test_sections_table.php` |
| `mild_from` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `mild_to` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `moderate_from` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `moderate_to` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `severe_from` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `severe_to` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `min_pass_score` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `section_no` | integer | `2025_06_20_071148_create_test_sections_table.php` |
| `section_instruction` | string | `2025_06_20_071148_create_test_sections_table.php` |
| `is_active` | boolean | `2025_10_15_101614_add_is_active_in_testsections_table.php` |
| `termination_type` | enum | `2026_03_23_105359_add_termination_type_and_threshold_to_test_sections_table.php` |
| `termination_threshold` | integer | `2026_03_23_105359_add_termination_type_and_threshold_to_test_sections_table.php` |
| `skip_if_section_passed` | tinyInteger | `2026_05_21_000001_add_section_progression_rules.php` |

_Dropped later by a migration (may still be listed above): `is_active`, `skip_if_section_passed`._

### `test_sessions` — `TABLE-041`

| Column | Type | Defined in |
|---|---|---|
| `test_invitation_id` | foreignId | `2026_03_02_180000_create_test_sessions_table.php` |
| `session_token` | string | `2026_03_02_180000_create_test_sessions_table.php` |
| `started_at` | timestamp | `2026_03_02_180000_create_test_sessions_table.php` |
| `expires_at` | timestamp | `2026_03_02_180000_create_test_sessions_table.php` |
| `test_invitation_id` | unsignedBigInteger | `2026_05_25_000001_make_test_invitation_id_nullable_in_test_sessions.php` |

### `testanswers` — `TABLE-042`

| Column | Type | Defined in |
|---|---|---|
| `unique_test_id` | string | `2025_06_20_070717_create_test_answers_table.php` |
| `test_id` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `test_section_plate_id` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `section_id` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `patient_id` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `plate_order` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `correct_answer` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `patient_answer` | integer | `2025_06_20_070717_create_test_answers_table.php` |
| `answered` | boolean | `2025_06_20_070717_create_test_answers_table.php` |
| `correct` | boolean | `2025_06_20_070717_create_test_answers_table.php` |
| `user_id` | integer | `2025_10_15_101139_add_multiple_fields_in_testanswers_table.php` |
| `right_eye` | tinyInteger | `2025_10_15_101139_add_multiple_fields_in_testanswers_table.php` |
| `is_demo` | tinyInteger | `2025_10_15_101139_add_multiple_fields_in_testanswers_table.php` |
| `display_order` | unsignedInteger | `2026_03_24_103149_add_display_order_to_test_answers.php` |
| `skip_reason` | enum | `2026_03_26_113100_add_skip_reason_to_testanswers_table.php` |

_Dropped later by a migration (may still be listed above): `display_order`, `skip_reason`._

### `tests` — `TABLE-043`

| Column | Type | Defined in |
|---|---|---|
| `title` | string | `2025_06_20_070605_create_test_table_table.php` |
| `allow_deficiency` | boolean | `2025_06_20_070605_create_test_table_table.php` |
| `private` | boolean | `2025_06_20_070605_create_test_table_table.php` |
| `description` | text | `2025_06_20_070605_create_test_table_table.php` |
| `instructions` | text | `2025_06_20_070605_create_test_table_table.php` |
| `image` | string | `2025_06_20_070605_create_test_table_table.php` |
| `user_ids` | text | `2025_10_15_100538_add_user_ids_to_test_table.php` |
| `slug` | string | `2026_05_08_000005_add_slug_to_tests_table.php` |

_Dropped later by a migration (may still be listed above): `user_ids`, `slug`._

### `transaction_details` — `TABLE-044`

| Column | Type | Defined in |
|---|---|---|
| `credit_id` | unsignedBigInteger | `2025_10_15_123744_create_transaction_details_table.php` |
| `raw_outgoing` | longText | `2025_10_15_123744_create_transaction_details_table.php` |
| `raw_response` | longText | `2025_10_15_123744_create_transaction_details_table.php` |
| `raw_coupon_code_id` | unsignedBigInteger | `2025_10_15_123744_create_transaction_details_table.php` |
| `raw_price_slabs` | text | `2025_10_15_123744_create_transaction_details_table.php` |
| `quantity` | integer | `2026_02_20_091431_alter_transaction_details_table.php` |
| `price_per_credit` | decimal | `2026_02_20_091431_alter_transaction_details_table.php` |
| `discount_amount` | decimal | `2026_02_20_091431_alter_transaction_details_table.php` |
| `discount_code` | string | `2026_02_20_091431_alter_transaction_details_table.php` |
| `total_amount` | decimal | `2026_02_20_091431_alter_transaction_details_table.php` |
| `original_amount` | decimal | `2026_03_30_152123_add_original_amount_column_to_transaction_detail_table.php` |
| `payment_method_type` | string | `2026_05_12_000002_add_card_details_to_transaction_details_table.php` |

_Dropped later by a migration (may still be listed above): `original_amount`, `payment_method_type`._

### `transactions` — `TABLE-045`

| Column | Type | Defined in |
|---|---|---|
| `stripe_payment_id` | string | `2025_07_08_082801_create_transactions_table.php` |
| `stripe_transaction_id` | string | `2025_07_08_082801_create_transactions_table.php` |
| `stripe_refund_id` | string | `2025_07_08_082801_create_transactions_table.php` |
| `user_id` | foreignId | `2025_07_08_082801_create_transactions_table.php` |
| `amount` | decimal | `2025_07_08_082801_create_transactions_table.php` |
| `refunded_amount` | decimal | `2025_07_08_082801_create_transactions_table.php` |
| `currency` | string | `2025_07_08_082801_create_transactions_table.php` |
| `status` | string | `2025_07_08_082801_create_transactions_table.php` |
| `ref_id` | foreignId | `2026_02_26_143534_add_column_to_transactions_table.php` |

_Dropped later by a migration (may still be listed above): `ref_id`._

### `user_assigned_tests` — `TABLE-046`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | foreignId | `2026_04_24_000001_create_user_assigned_tests_table.php` |
| `test_id` | foreignId | `2026_04_24_000001_create_user_assigned_tests_table.php` |

### `user_email_settings` — `TABLE-047`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | unsignedBigInteger | `2025_10_15_124114_create_user_email_settings_table.php` |
| `subject` | string | `2025_10_15_124114_create_user_email_settings_table.php` |
| `body` | longText | `2025_10_15_124114_create_user_email_settings_table.php` |
| `type` | string | `2025_10_15_124114_create_user_email_settings_table.php` |

### `user_email_templates` — `TABLE-048`

_No columns detected (index/constraint-only migrations)._

### `user_emails` — `TABLE-049`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | unsignedBigInteger | `2025_07_02_070723_create_user_emails_table.php` |
| `subject` | string | `2025_07_02_070723_create_user_emails_table.php` |
| `body` | longText | `2025_07_02_070723_create_user_emails_table.php` |
| `type` | string | `2025_07_02_070723_create_user_emails_table.php` |

### `user_hidden_tests` — `TABLE-050`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | foreignId | `2026_04_01_122406_create_user_hidden_tests_table.php` |
| `test_id` | foreignId | `2026_04_01_122406_create_user_hidden_tests_table.php` |

### `user_stripe_details` — `TABLE-051`

| Column | Type | Defined in |
|---|---|---|
| `user_id` | foreignId | `2026_02_12_145901_create_user_stripe_details_table.php` |
| `stripe_customer_id` | string | `2026_02_12_145901_create_user_stripe_details_table.php` |
| `payment_method_id` | string | `2026_02_12_145901_create_user_stripe_details_table.php` |

### `users` — `TABLE-052`

| Column | Type | Defined in |
|---|---|---|
| `name` | string | `0001_01_01_000001_create_users_table.php` |
| `email` | string | `0001_01_01_000001_create_users_table.php` |
| `email_verified_at` | timestamp | `0001_01_01_000001_create_users_table.php` |
| `password` | string | `0001_01_01_000001_create_users_table.php` |
| `first_name` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `last_name` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `company_name` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `address` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `city` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `state_id` | unsignedBigInteger | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `country_id` | unsignedBigInteger | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `zip_code` | string | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `account_status` | enum | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `email_verified` | enum | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `show_occupational_questions` | boolean | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `allow_monocular_test` | boolean | `2025_06_12_090025_update_users_table_add_multiple_fields.php` |
| `usertype` | unsignedTinyInteger | `2025_06_17_140545_add_usertype_to_users_table.php` |
| `includeWaggnorCCVT` | boolean | `2025_06_23_093311_update_users_table_add_test_fields.php` |
| `includeColorVisionTesting` | boolean | `2025_06_23_093311_update_users_table_add_test_fields.php` |
| `includeOlderChildrenCCVT` | boolean | `2025_06_23_093311_update_users_table_add_test_fields.php` |
| `includeWaggnorCCVT10Sec` | boolean | `2025_06_23_093311_update_users_table_add_test_fields.php` |
| `password_setup_token` | string | `2025_06_26_095941_add_email_verification_token_to_users_table.php` |
| `email_verification_token` | string | `2025_06_26_095941_add_email_verification_token_to_users_table.php` |
| `occupation_purpose` | tinyInteger | `2025_10_15_100326_add_additional_fields_to_users_table.php` |
| `send_result_lrs` | tinyInteger | `2025_10_15_100326_add_additional_fields_to_users_table.php` |
| `hide_tests` | text | `2025_10_15_100326_add_additional_fields_to_users_table.php` |
| `both_eyes` | tinyInteger | `2025_10_15_100326_add_additional_fields_to_users_table.php` |
| `remote_addr` | string | `2025_10_15_100326_add_additional_fields_to_users_table.php` |
| `email_verification_expires_at` | timestamp | `2026_04_14_000001_add_email_verification_expires_at_to_users_table.php` |
| `phone_no` | string | `2026_04_20_160452_add_phone_no_to_users_table.php` |

_Dropped later by a migration (may still be listed above): `usertype`, `includeWaggnorCCVT`, `includeColorVisionTesting`, `includeOlderChildrenCCVT`, `includeWaggnorCCVT10Sec`, `name`, `email_verification_expires_at`, `phone_no`._

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-08-28. Do not hand-edit — re-run the generator._
