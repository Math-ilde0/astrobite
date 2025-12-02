# Admin Dashboard Setup Guide

## Quick Start

Your admin dashboard is ready! Here's what you need to do:

### 1. **Make Your Account an Admin** (First Time Setup)

Run this SQL query in your database to make your account an admin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

Replace `your-email@example.com` with your actual email.

### 2. **Access the Admin Panel**

1. Login at `/login.php` with your admin account
2. Go to `/admin/index.php` to see the dashboard
3. Or go directly to `/admin/dashboard.php` to manage orders

### 3. **What You Can Do**

**Admin Dashboard (`/admin/dashboard.php`)**
- View all orders with status filtering (Pending, Ready for Pickup, Completed, Cancelled)
- Quick status: Shows Order ID, Customer, Amount, Status, Collection Point, and Date
- Click "Update" button to change order status
- See up to 50 orders at a time

**Admin Home (`/admin/index.php`)**
- View key statistics:
  - Total Orders
  - Pending Orders
  - Total Revenue
- Quick navigation to Order Dashboard

### 4. **Order Statuses**

- **Pending** ⏳ - New orders waiting to be processed
- **Ready for Pickup** ✅ - Order is ready at the collection point
- **Completed** ✔️ - Order has been delivered or picked up
- **Cancelled** ❌ - Order was cancelled

### 5. **Features**

✅ Simple modal popup to update order status
✅ Filter orders by status
✅ View customer name and email
✅ See order total and collection point
✅ Responsive design for mobile
✅ Only accessible to admin users

## How It Works

1. **Admin Authentication**: The system checks if a user has `role = 'admin'` in the database
2. **Order Management**: Update statuses instantly with the modal dialog
3. **Access Control**: Non-admin users are redirected to login page
4. **Session Security**: User role is loaded from database on each login

## SQL to Set Up Multiple Admins

```sql
UPDATE users SET role = 'admin' WHERE user_id IN (1, 2, 3);
```

Or make a specific user admin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
```

## Security Notes

- Admin access is based on the `role` field in the `users` table
- Only users with `role = 'admin'` can access `/admin/`
- Session data includes the user role for quick checks
- All changes are validated on the server side

Enjoy your new admin dashboard! 🚀
