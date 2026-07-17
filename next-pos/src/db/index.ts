import { adminDb } from '@/lib/firebase-admin';

// ---------------------------------------------------------------------------
// TypeScript interfaces (mirrors old SQLite schema)
// ---------------------------------------------------------------------------

export interface Branch {
  id?: string;
  name: string;
  code: string;
  phone?: string;
  email?: string;
  address?: string;
  status: 'active' | 'inactive';
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface User {
  id?: string;
  name: string;
  email: string;
  password: string;
  branchId?: string;
  status: 'active' | 'inactive';
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface Role {
  id?: string;
  name: string;
}

export interface Permission {
  id?: string;
  name: string;
}

export interface Category {
  id?: string;
  name: string;
  slug: string;
}

export interface Brand {
  id?: string;
  name: string;
  slug: string;
}

export interface Tax {
  id?: string;
  name: string;
  rate: number;
  status: 'active' | 'inactive';
}

export interface Product {
  id?: string;
  name: string;
  sku: string;
  barcode?: string;
  price: number;
  cost: number;
  categoryId?: string;
  brandId?: string;
  taxId?: string;
  stockQuantity: number;
  status: 'active' | 'inactive';
  image?: string;
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface ProductStock {
  id?: string;
  productId: string;
  branchId: string;
  stockQuantity: number;
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface Customer {
  id?: string;
  name: string;
  phone?: string;
  email?: string;
  address?: string;
  loyaltyPoints: number;
  status: 'active' | 'inactive';
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface Order {
  id?: string;
  offlineId?: string;
  branchId: string;
  userId: string;
  customerId?: string;
  subtotal: number;
  discountAmount: number;
  taxAmount: number;
  totalAmount: number;
  paidAmount: number;
  changeDue: number;
  paymentStatus: 'paid' | 'partially_paid' | 'unpaid';
  syncStatus: 'synced' | 'pending';
  createdAt?: FirebaseFirestore.Timestamp | string;
  updatedAt?: FirebaseFirestore.Timestamp | string;
}

export interface OrderItem {
  id?: string;
  orderId: string;
  productId: string;
  price: number;
  quantity: number;
  discount: number;
  total: number;
}

export interface SalePayment {
  id?: string;
  orderId: string;
  paymentMethod: 'cash' | 'card' | 'mobile' | 'loyalty';
  amount: number;
  transactionId?: string;
  status: 'completed' | 'pending' | 'failed';
  createdAt?: FirebaseFirestore.Timestamp | string;
}

export interface Supplier {
  id?: string;
  name: string;
  companyName?: string;
  email?: string;
  phone?: string;
  address?: string;
  status: 'active' | 'inactive';
  createdAt?: FirebaseFirestore.Timestamp | string;
}

export interface PurchaseOrder {
  id?: string;
  offlineId?: string;
  supplierId: string;
  branchId: string;
  subtotal: number;
  discountAmount: number;
  taxAmount: number;
  totalAmount: number;
  status: 'ordered' | 'received' | 'cancelled';
  createdAt?: FirebaseFirestore.Timestamp | string;
}

export interface PurchaseOrderItem {
  id?: string;
  purchaseOrderId: string;
  productId: string;
  unitCost: number;
  quantity: number;
  receivedQuantity: number;
}

export interface ExpenseCategory {
  id?: string;
  name: string;
  description?: string;
}

export interface Expense {
  id?: string;
  title: string;
  categoryId: string;
  amount: number;
  note?: string;
  createdAt?: FirebaseFirestore.Timestamp | string;
}

export interface ActivityLog {
  id?: string;
  userId: string;
  action: string;
  description?: string;
  createdAt?: FirebaseFirestore.Timestamp | string;
}

// Collection references (using adminDb for server-side)
const collections = {
  branches: () => adminDb.collection('branches'),
  users: () => adminDb.collection('users'),
  roles: () => adminDb.collection('roles'),
  permissions: () => adminDb.collection('permissions'),
  roleUsers: () => adminDb.collection('roleUsers'),
  permissionRoles: () => adminDb.collection('permissionRoles'),
  categories: () => adminDb.collection('categories'),
  brands: () => adminDb.collection('brands'),
  taxes: () => adminDb.collection('taxes'),
  products: () => adminDb.collection('products'),
  productStocks: () => adminDb.collection('productStocks'),
  customers: () => adminDb.collection('customers'),
  orders: () => adminDb.collection('orders'),
  orderItems: () => adminDb.collection('orderItems'),
  salePayments: () => adminDb.collection('salePayments'),
  suppliers: () => adminDb.collection('suppliers'),
  purchaseOrders: () => adminDb.collection('purchaseOrders'),
  purchaseOrderItems: () => adminDb.collection('purchaseOrderItems'),
  expenseCategories: () => adminDb.collection('expenseCategories'),
  expenses: () => adminDb.collection('expenses'),
  activityLogs: () => adminDb.collection('activityLogs'),
};

export { collections };
export { adminDb };
