import { adminDb } from '@/lib/firebase-admin';
import bcrypt from 'bcryptjs';

async function main() {
  console.log('Seeding Firestore database...');
  const timestamp = new Date();

  // 1. Create a Branch
  const branchRef = adminDb.collection('branches').doc();
  await branchRef.set({
    name: 'Main Branch',
    code: 'MAIN',
    phone: '01712345678',
    email: 'main@apfpos.com',
    address: 'Dhaka, Bangladesh',
    status: 'active',
    createdAt: timestamp,
    updatedAt: timestamp,
  });
  const branchId = branchRef.id;
  console.log(`Branch created: Main Branch (ID: ${branchId})`);

  // 2. Create User Roles
  const adminRoleRef = adminDb.collection('roles').doc();
  await adminRoleRef.set({ name: 'admin' });

  const cashierRoleRef = adminDb.collection('roles').doc();
  await cashierRoleRef.set({ name: 'cashier' });

  const adminRoleId = adminRoleRef.id;
  const cashierRoleId = cashierRoleRef.id;

  // 3. Create Users
  const adminPassword = await bcrypt.hash('admin123', 10);
  const cashierPassword = await bcrypt.hash('cashier123', 10);

  const adminUserRef = adminDb.collection('users').doc();
  await adminUserRef.set({
    name: 'System Admin',
    email: 'admin@apfpos.com',
    password: adminPassword,
    branchId,
    status: 'active',
    createdAt: timestamp,
    updatedAt: timestamp,
  });
  const adminUserId = adminUserRef.id;

  const cashierUserRef = adminDb.collection('users').doc();
  await cashierUserRef.set({
    name: 'Terminal Cashier',
    email: 'cashier@apfpos.com',
    password: cashierPassword,
    branchId,
    status: 'active',
    createdAt: timestamp,
    updatedAt: timestamp,
  });
  const cashierUserId = cashierUserRef.id;

  console.log(`Users created: admin@apfpos.com (ID: ${adminUserId}), cashier@apfpos.com (ID: ${cashierUserId})`);

  // Link users to roles
  await adminDb.collection('roleUsers').add({ userId: adminUserId, roleId: adminRoleId });
  await adminDb.collection('roleUsers').add({ userId: cashierUserId, roleId: cashierRoleId });

  // 4. Create Categories
  const catBeveragesRef = adminDb.collection('categories').doc();
  await catBeveragesRef.set({ name: 'Beverages', slug: 'beverages' });

  const catSnacksRef = adminDb.collection('categories').doc();
  await catSnacksRef.set({ name: 'Snacks', slug: 'snacks' });

  const catElectronicsRef = adminDb.collection('categories').doc();
  await catElectronicsRef.set({ name: 'Electronics', slug: 'electronics' });

  const catBeveragesId = catBeveragesRef.id;
  const catSnacksId = catSnacksRef.id;
  const catElectronicsId = catElectronicsRef.id;

  // 5. Create Brands
  const brandApfRef = adminDb.collection('brands').doc();
  await brandApfRef.set({ name: 'APF Foods', slug: 'apf-foods' });

  const brandGenericRef = adminDb.collection('brands').doc();
  await brandGenericRef.set({ name: 'Generic', slug: 'generic' });

  const brandApfId = brandApfRef.id;
  const brandGenericId = brandGenericRef.id;

  // 6. Create Tax
  const taxRef = adminDb.collection('taxes').doc();
  await taxRef.set({ name: 'VAT (15%)', rate: 0.15, status: 'active' });

  const taxId = taxRef.id;
  console.log('Categories, Brands, and Taxes created.');

  // 7. Create Products
  const mockProducts = [
    {
      name: 'Coca Cola 250ml',
      sku: 'COKE250',
      barcode: '8801007785312',
      price: 1.50,
      cost: 0.90,
      categoryId: catBeveragesId,
      brandId: brandApfId,
      taxId,
      stockQuantity: 150,
      status: 'active',
    },
    {
      name: 'Potato Chips Spicy',
      sku: 'CHIPS-SPICY',
      barcode: '8801007785329',
      price: 2.00,
      cost: 1.20,
      categoryId: catSnacksId,
      brandId: brandApfId,
      taxId,
      stockQuantity: 80,
      status: 'active',
    },
    {
      name: 'Wireless Bluetooth Headset',
      sku: 'HEADSET-WL',
      barcode: '8801007785336',
      price: 45.00,
      cost: 25.00,
      categoryId: catElectronicsId,
      brandId: brandGenericId,
      taxId,
      stockQuantity: 20,
      status: 'active',
    },
  ];

  for (const prod of mockProducts) {
    const prodRef = adminDb.collection('products').doc();
    await prodRef.set({
      ...prod,
      createdAt: timestamp,
      updatedAt: timestamp,
    });
    // Also add to branch product stock
    await adminDb.collection('productStocks').add({
      productId: prodRef.id,
      branchId,
      stockQuantity: prod.stockQuantity,
      createdAt: timestamp,
      updatedAt: timestamp,
    });
  }

  console.log('Products and Product Stocks seeded.');

  // 8. Create Customers
  await adminDb.collection('customers').add({
    name: 'Walk-in Customer',
    phone: '00000000000',
    email: 'walkin@apfpos.com',
    address: 'N/A',
    loyaltyPoints: 0,
    status: 'active',
    createdAt: timestamp,
    updatedAt: timestamp,
  });

  await adminDb.collection('customers').add({
    name: 'Niyamul Hasan',
    phone: '01711122233',
    email: 'niyamul@apfpos.com',
    address: 'Dhaka, Bangladesh',
    loyaltyPoints: 120,
    status: 'active',
    createdAt: timestamp,
    updatedAt: timestamp,
  });

  console.log('Customers seeded.');
  console.log('Database seeding completed successfully!');
}

main().catch((err) => {
  console.error('Seeding failed:', err);
  process.exit(1);
});
