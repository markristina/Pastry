-- Fix: Add missing payment_method and payment_status columns to orders table
-- Run this SQL in phpMyAdmin or via command line to fix the "Unknown column 'payment_method'" error

ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL DEFAULT 'cash_on_delivery' AFTER customer_postal;
ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) NULL DEFAULT 'pending' AFTER payment_method;
