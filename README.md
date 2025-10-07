# PHP Invoice App (BASE_URL ready)

This build is set for: `https://webdesigner.com.in/invoice` (see `config.php` → `BASE_URL`).

## Deploy
1) Upload all files to `public_html/invoice/` on GoDaddy.  
2) Create MySQL DB + user.  
3) Import `init.sql` via phpMyAdmin.  
4) Edit DB credentials in `config.php`.  
5) Replace `assets/img/logo.png` with your logo.  

## Notes
- Auto invoice numbers follow Indian FY (Apr–Mar), e.g., `ITS/25-26/0027`.  
- Print page: `/invoices/print.php?id=###` opens print-friendly view (good for "Save as PDF").  
- No login in this demo. Protect the folder or add auth before public use.
