# Laravel Xero Demo

This application provides integration with Xero accounting software for managing contacts and invoices.

## Features

### Xero Contacts Module

The Contacts module provides comprehensive management of Xero contacts with the following features:

- **List and Filter Contacts**: View all contacts with advanced filtering options including search, account number, email, and status filters.
- **Create Contacts**: Add new contacts to Xero with detailed information including name, email, address, phone numbers, and more.
- **Edit Contacts**: Update existing contact information with a user-friendly form interface.
- **View Contact Details**: See comprehensive contact information including addresses and phone numbers.
- **Archive Contacts**: Archive contacts individually or in bulk using the selection checkboxes.
- **Export to CSV**: Export all contacts or selected contacts to CSV format for external use.
- **Import from CSV**: Import contacts from CSV files with intelligent column mapping and duplicate detection.
  - Automatic column mapping with manual override options
  - Updates existing contacts instead of creating duplicates
  - Background processing with real-time progress updates
  - Detailed error reporting and success summaries

### Xero Invoices Module

The Invoices module provides comprehensive management of Xero invoices with the following features:

- **List and Filter Invoices**: View all invoices with advanced filtering options including invoice number, reference, date range, and status filters.
- **Create Invoices**: Create new invoices with detailed information including contact selection, line items, dates, and tax settings.
- **Edit Invoices**: Update existing invoices with a user-friendly form interface.
- **View Invoice Details**: See comprehensive invoice information including line items, totals, and attachments.
- **Export to CSV**: Export all invoices or selected invoices to CSV format for external use.
- **Online Invoice Access**: Direct links to view invoices in the Xero online portal.

## Technical Implementation

- Built with Laravel and Livewire for reactive UI components
- Uses the dcblogdev/laravel-xero package for Xero API integration
- Implements background job processing for resource-intensive operations
- Includes comprehensive error handling and user feedback
- Responsive design for desktop and mobile use

## Getting Started

1. Configure your Xero API credentials in the `.env` file
2. Run database migrations
3. Access the Xero dashboard at `/admin/xero`
4. Connect to your Xero account when prompted
5. Start managing your contacts and invoices

## Requirements

- PHP 8.2+
- Laravel 12+
- Xero API credentials (Client ID and Client Secret)
