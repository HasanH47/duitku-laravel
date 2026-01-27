# Task: Implement Duitku Disbursement API

## Phase 1: Configuration & Foundation

- [ ] **Config Update**: Add `user_id` and `email` to `config/duitku.php` and `DuitkuConfig` class.
- [ ] **DTO Creation**:
  - [ ] `DisbursementInfo` (Bank Code, Account Number, Amount, etc.)
  - [ ] `DisbursementStatus`

## Phase 2: Core Implementation

- [ ] **Disbursement Class**: Create `src/Disbursement.php` (Separate from `Duitku` core for clean separation).
- [ ] **Signature Logic**: Implement specific signature generation for Disbursement (Inquiry & Transfer).
- [ ] **API Methods**:
  - [ ] `bankInquiry(DisbursementInfo $info)`
  - [ ] `transfer(DisbursementInfo $info, string $disburseId)`
  - [ ] `checkStatus(string $disburseId)`

## Phase 3: Integration & Testing

- [ ] **Facade**: Update or Create new Facade if needed (or just hang it under `Duitku::disbursement()`).
- [ ] **Tests**: Add unit tests with `Http::fake`.
- [ ] **Documentation**: Update `README.md` and `walkthrough.md`.
