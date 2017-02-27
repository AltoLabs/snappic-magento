# Acceptance Tests

## Checks

### A

- The product can be found: `p = s.products.find_by handle: sku`.
- Check that the attributes are mirrored, type `pp p` for a quick overview.
- Check that the images match: `pp p.product_images`.


## Tests

### Standalone Simple Product Tests

#### Tests

> Creation of product `psp`:

- In the first step, select `Simple Product`.
- **General**
  - Name / Description / Short Description: `Pierre's Simple Product`
  - URL key / SKU: `psp`
  - Weight: `1`
  - Status: `Enabled`
  - Visibility: `Catalog, Search`
- **Prices**
  - Price: `50`
  - Tax Class: `Taxable Goods`
- **Inventory**
  - Quantity: `5`
  - Stock Availability: `In Stock`
- **Images**
  - Upload at least one

> Outcome

- Run checks **A** using `sku = 'psp'`.
- Make sure that a fake variant with negative external ID exist (`pp p.product_variants`).
- Pay special attention to the quantity, it must be **5**.

---

> Edition of product `psp`:

- **General**
  - Name / Description / Short Description: `Pierre's Edited Simple Product`
- **Prices**
  - Price: `100`
- **Inventory**
  - Quantity: `10`
- **Images**
  - Change the picture for another one.

> Outcome

- Run checks **A** using `sku = 'psp'`.
- Make sure that a fake variant with negative external ID exist (`pp p.product_variants`).
- Check all edited values.
- The picture should be different.
- Pay special attention to the quantity, it must be **10**.

---

> Edition of product `psp`:

- **Inventory**
  - Stock Svailability: `Out of Stock`.

> Outcome

- Run checks **A** using `sku = 'psp'`.
- Make sure that a fake variant with negative external ID exist (`pp p.product_variants`).
- Pay special attention to the quantity, it must be **0**.

---

> Edition of product `psp`:

- **Inventory**
  - Quantity: `15`
  - Stock Availability: `In Stock`

> Outcome

- Run checks **A** using `sku = 'psp'`.
- Make sure that a fake variant with negative external ID exist (`pp p.product_variants`).
- Pay special attention to the quantity, it must be **15**.

#### Inventory Truth Matrix

| Qty | In Stock | Snappic Qty |
|-----|----------|-------------|
| 0   | N        | 0           |
| 10  | N        | 0           |
| 0   | Y        | 0           |
| 10  | Y        | 10          |


### Configurable Product Tests

> Creation of product `pcp`:

- In the first step, select `My Attribute Set` and `Configurable Product`, then check `Color` and `Size`.
- **General**
  - Name / Description / Short Description: `Pierre's Configurable Product`
  - URL key / SKU: `pcp`
- **Prices**
  - Price: `50`
  - Tax Class: `Taxable Goods`
- **Inventory**
  - Stock Availability: `In Stock`
- **Images**
  - Upload at least one

> Outcome

- Run checks **A** using `sku = 'pcp'`.
- Make sure that a fake variant with negative external ID exist (`pp p.product_variants`).
- Pay special attention to the quantity, it must be **0**.

---

> Addition of subproduct `pcp-Black-11`:

- From `pcp` edition, navigate to "Associated Products" and focus on the "Quick simple product creation" form:
  - Weight: `1`
  - Status: `Enabled`
  - Visibility: `Catalog, Search`
  - Color: `Black` -> Price: `40`
  - Size: `11` -> Price: `30`
  - Qty: `0`
  - Stock Availability: `Out of Stock`
- Save the subproduct, and save the configurable.

> Outcome

- Run checks **A** using `sku = 'pcp'`.
- Make sure that the fake variant was replaced with the correct one (`pp p.product_variants`).
- The product's price must be **120**. **Asking Pierre why is irrelevant**. Remember that against Magento, you're just a mere mortal and resistance is futile.
- Pay special attention to the quantity, it must be **0**.
- Check the variant quantity as well, it must be **0**.

---

> Addition of subproduct `pcp-Green-XS`:

- From `pcp` edition, navigate to "Associated Products" and focus on the "Quick simple product creation" form:
  - Weight: `1`
  - Status: `Enabled`
  - Visibility: `Catalog, Search`
  - Color: `Green` -> Price: `40`
  - Size: `XS` -> Price: `30`
  - Qty: `10`
  - Stock Availability: `In Stock`
- Save the subproduct, and save the configurable.

> Outcome

- Run checks **A** using `sku = 'pcp'`.
- Make sure that the another variant was created (`pp p.product_variants`).
- The product's price must be **120**.
- Pay special attention to the quantity, it must be **10**.
- Check the variant quantity as well, it must be **10**.

---

> Remove subproduct `pcp-Green-XS`:

- From the `pcp` product, uncheck the `pcp-Green-XS` subproduct and save.

> Outcome

- The configurable must be back to a quantity of `0`.
- The number of variants in the product must be down to one.
- A new product should exist with handle `pcp-Green-XS`, and quantity of 10.

---

> Delete product `pcp-Green-XS`:

- From the catalog management system, destroy the `pcp-Green-XS` simple product.

> Outcome:

- The product should have also been deleted in our system.

#### Inventory Truth Matrix

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | N        | 0           |
| Subproduct   | 0   | N        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | N        | 0           |
| Subproduct   | 0   | Y        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | N        | 0           |
| Subproduct   | 10  | N        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | N        | 0           |
| Subproduct   | 10  | Y        | 0           |


| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | Y        | 0           |
| Subproduct   | 0   | N        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | Y        | 0           |
| Subproduct   | 0   | Y        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | Y        | 0           |
| Subproduct   | 10  | N        | 0           |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | Y        | 0           |
| Subproduct   | 10  | Y        | 10          |

| Type         | Qty | In Stock | Snappic Qty |
|--------------|-----|----------|-------------|
| Configurable |     | Y        | 20          |
| Subproduct 1 | 10  | Y        | 10          |
| Subproduct 2 | 10  | Y        | 10          |
