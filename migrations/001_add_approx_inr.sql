-- Add approx_inr_value column to invoices to store approximate INR equivalent when currency is not INR
ALTER TABLE invoices
  ADD COLUMN approx_inr_value DECIMAL(15,2) NULL DEFAULT NULL;
