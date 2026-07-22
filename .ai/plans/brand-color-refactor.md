# Brand Color Refactor — Plan

> Token mapping (đã có từ commit `5c286b8`):
> - `--accent` = `#D9622B` (cam đất, chủ đạo)
> - `--brand-secondary` = `#0E5C57` (teal, tin cậy)
> - `--bg` = `#F5EEF4`, `--fg` = `#1C1C1A`

## Trạng thái hiện tại (audit 16/07/2026)

| Element | Current | Mục tiêu | Source file:line |
|---|---|---|---|
| **CTA "Thêm vào giỏ"** (pcard) | `var(--accent)` border + fill on hover ✓ | (đúng, giữ) | pixel-cam.css:408-411 |
| **CTA "Mua ngay"** (slider) | `var(--accent)` hover only — bg trắng | border `var(--accent)` | pixel-cam.css:229-230 |
| **CTA "Tìm kiếm"** (header) | `var(--accent)` bg ✓ | (đúng) | pixel-cam.css:61 |
| **Mini-cart checkout** | `var(--accent)` bg ✓ | (đúng) | pixel-cam.css:176 |
| **Price `.price .now`** | `var(--danger)` `#DC2626` đỏ tươi | **`var(--accent)` cam đất** | pixel-cam.css:405 |
| **Badge sale `.bd.sale`** | `var(--danger)` đỏ tươi | **`var(--accent)` cam đất** | pixel-cam.css:390 |
| **Badge new `.bd.new`** | `var(--success)` `#17A34A` xanh lá | **`var(--brand-secondary)` teal** | pixel-cam.css:391 |
| **Hot badge `.bd.hot`** | `var(--warn)` vàng + fg text | **giữ** (vàng chuẩn universal) | pixel-cam.css:392 |
| **Notification bubble** (cart/wish count) | `var(--danger)` đỏ tươi | **`var(--accent)` cam đất** | pixel-cam.css:68 |
| **Wishlist heart `.wish:hover`/`.on`** | `var(--danger)` đỏ tươi | **`var(--accent)` cam đất** | pixel-cam.css:396-397 |
| **Post-tag category** (blog) | `#DF062D` đỏ tươi hardcoded | **`var(--accent)` cam đất** | pixel-cam.css:740, 743 |
| **Post author ring** (mobile) | `#ef1515` đỏ tươi hardcoded | **`var(--accent)` cam đất** | pixel-cam.css:809 |
| **Post author name** (mobile) | `#DF062D` đỏ tươi hardcoded | **`var(--accent)` cam đất** | pixel-cam.css:811 |
| **PDP gifts box** (dashed border) | `var(--accent)` ✓ border + bg | (đúng, giữ) | pixel-cam.css:940 |
| **Stock status dot** (in-stock) | `#16A34A` xanh lá hardcoded | **`var(--brand-secondary)` teal** | pixel-cam.css:901 |
| **Stock status dot** (out-of-stock) | `var(--danger)` đỏ tươi | **`var(--accent)` cam đất** | pixel-cam.css:1783 |
| **Review verified badge** | `#16A34A` xanh lá | **`var(--brand-secondary)` teal** | pixel-cam.css:999 |
| **Trust strip icons** (cam kết ở home) | `var(--accent)` ✓ | (đúng, giữ) | pixel-cam.css:261 |
| **PDP perks/gift/box icons** | `var(--accent)` ✓ | (đúng, giữ) | pixel-cam.css:935, 942, 951 |
| **Active menu link** | `var(--accent)` ✓ | (đúng, giữ) | (đã có) |
| **Breadcrumb current** | `var(--fg)` đen (giữ) | giữ | pixel-cam.css:515 |
| **Breadcrumb link hover** | `var(--accent)` ✓ | (đúng, giữ) | pixel-cam.css:513 |
| **Hotline/Zalo text** (contact) | chưa có trong code (render dạng plain text) | cần check visual sau | — |
| **CTA "Mua ngay" (slide)** | bg trắng, hover → accent | đổi sang border accent + accent text default | pixel-cam.css:229-230 |

---

## Plan A — Brand Color Refactor (8 nhóm, 1 commit)

### A1. Price color: đỏ tươi → cam đất
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.price .now` (line 405): `color: var(--danger)` → `color: var(--accent)`
  - `.pdp-info .price` (line 1822+): review nếu có rule override với danger
- **Risk**: Low — semantic, accent works for price (urgency, "chú ý")

### A2. Sale badge: đỏ tươi → cam đất
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.bd.sale` (line 390): `background: var(--danger)` → `background: var(--accent)`
- **Risk**: Low

### A3. New badge: xanh lá → teal
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.bd.new` (line 391): `background: var(--success)` → `background: var(--brand-secondary)`
- **Risk**: Low

### A4. Notification bubble: đỏ tươi → cam đất
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.ha .badge` (line 68): `background: var(--danger)` → `background: var(--accent)`
- **Risk**: Low — badge count 0/0 hiện không có màu, chỉ khi >0

### A5. Wishlist heart: đỏ tươi → cam đất
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.wish:hover svg` (line 396): `stroke: var(--danger)` → `stroke: var(--accent)`
  - `.wish.on svg` (line 397): `stroke: var(--danger); fill: var(--danger)` → `stroke: var(--accent); fill: var(--accent)`
  - `.pdp-info form.cart .wish.ic:hover` (line 2021+): tương tự
  - `.pdp-info form.cart .wish.ic.on` (line 2025+): tương tự
- **Risk**: Low

### A6. Stock status dot: xanh lá → teal
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.pdp-stock .dot` (line 901): `background: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,.18)` → `background: var(--brand-secondary); box-shadow: 0 0 0 3px rgba(14,92,87,.18)`
  - `.pdp-stock .dot--out` (line 1783): `background: var(--danger)` → `background: var(--accent)` (out-of-stock cũng dùng cam)
- **Risk**: Low — semantic: còn hàng = teal "tin cậy", hết hàng = cam "chú ý"

### A7. Review verified badge: xanh lá → teal
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.review-item .head .ver` (line 999): `color: #16A34A; background: rgba(22,163,74,.1)` → `color: var(--brand-secondary); background: rgba(14,92,87,.1)`
- **Risk**: Low

### A8. Post-tag + author ring/name: đỏ tươi → cam đất (mobile post)
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.post-tag` (line 740): `background: #DF062D` → `background: var(--accent)`
  - `.post-tag:hover` (line 743): `background: #c70528` → `background: var(--accent-80)` (hover 80% alpha)
  - `.post-author__av` (mobile, line 809): `border: 1px solid #ef1515` → `border: 1px solid var(--accent)`
  - `.post-author__name` (mobile, line 811): `color: #DF062D` → `color: var(--accent)`
- **Risk**: Low — category tags + author color all in mobile post card

### A9 (bonus). CTA "Mua ngay" trên slider
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.slide .cap .btn` (line 229): add `border: 1px solid var(--accent)` + `color: var(--accent)` để default state có viền cam + chữ cam
  - Hover giữ nguyên: bg accent + chữ trắng
- **Risk**: Low — minor visual change

### A10 (cleanup). Verify tất cả `var(--danger)` còn lại
- **File**: `assets/css/pixel-cam.css`
- **Action**: Grep `--danger` còn lại ở đâu ngoài những chỗ giữ (out-of-stock, form errors)
- **Expected**: giữ ở error states (form validation, stock out alert)

---

## Plan B — Shop archive 5-col grid (1 commit riêng)

### B1. Grid: 3-col → 5-col desktop
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.woocommerce ul.products` (line 1732): `grid-template-columns: repeat(3, minmax(0, 1fr))` → `repeat(5, minmax(0, 1fr)); gap: 16px`
  - `≤980px` (line 1733): `repeat(2, minmax(0, 1fr))` → `repeat(3, minmax(0, 1fr))` (tablet 3-col)
  - `≤560px` (line 1733): mobile 2-col (giữ)
- **Risk**: Low — layout change, no JS impact

### B2. Card compact (sản phẩm nhỏ hơn, padding gọn)
- **File**: `assets/css/pixel-cam.css`
- **Change**:
  - `.pcard .body` (line 398): `padding: 12px 14px 14px` → `padding: 10px 12px 12px` (gọn hơn)
  - `.pcard .name` (line 401): `font-size: 14px` → `font-size: 13px` (gọn hơn 1 chút)
  - `.pcard .price .now` (line 405): `font-size: 17px` → `font-size: 15px` (gọn hơn 1 chút)
  - `.pcard .addcart`: check font-size, có thể giảm `padding` từ default xuống 8px
- **Risk**: Medium — font size nhỏ có thể ảnh hưởng readability, cần verify screenshot

---

## Thứ tự commit

1. **Commit 1** (Plan A): Brand color refactor — 1 commit, ~10 file changes to `pixel-cam.css`. Không thay đổi HTML/JS.
2. **Commit 2** (Plan B): Shop archive 5-col — 1 commit, ~5 line changes to `pixel-cam.css`.

Mỗi commit verify bằng Playwright screenshots trước khi push.

## Out of scope (cần user confirm)

- Logo header (`cropped-logo.png`) — hiện màu cam đất đúng rồi (đã check từ turn trước)
- Footer `.foot-biz` link hover — không có quy định riêng, giữ `--accent`
- **Hotline/Zalo** trong trang liên hệ — render là plain text từ `general_section` ACF, không có color rule riêng. Có thể thêm rule nếu user muốn nổi bật hơn.

## Rủi ro tổng thể

- **Visual jarring** khi áp dụng nhiều màu cùng lúc → cần verify screenshot sau commit
- **Brand consistency** → cam đất sẽ xuất hiện ở nhiều chỗ (CTA, price, badge sale, bubble, wishlist) → risk "quá nhiều accent" nhưng đúng guideline
- **Auto-deploy vẫn stuck** → cần user SSH pull manually sau khi push
