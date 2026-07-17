import { NextAuthOptions } from 'next-auth';
import CredentialsProvider from 'next-auth/providers/credentials';
import { adminDb } from '@/lib/firebase-admin';
import bcrypt from 'bcryptjs';

export const authOptions: NextAuthOptions = {
  providers: [
    CredentialsProvider({
      name: 'Credentials',
      credentials: {
        email: { label: 'Email', type: 'text', placeholder: 'cashier@apfpos.com' },
        password: { label: 'Password', type: 'password' },
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) {
          throw new Error('Please enter an email and password');
        }

        // Find user in Firestore
        const usersRef = adminDb.collection('users');
        const snapshot = await usersRef.where('email', '==', credentials.email).limit(1).get();

        if (snapshot.empty) {
          throw new Error('No user found with that email');
        }

        const userDoc = snapshot.docs[0];
        const user = userDoc.data();

        if (user.status !== 'active') {
          throw new Error('Your account is inactive. Please contact support.');
        }

        // Compare password hash
        const passwordMatch = await bcrypt.compare(credentials.password, user.password);

        if (!passwordMatch) {
          throw new Error('Incorrect password');
        }

        // Fetch roles
        const roleUsersRef = adminDb.collection('roleUsers');
        const roleSnapshot = await roleUsersRef.where('userId', '==', userDoc.id).get();

        let role = 'cashier';
        if (!roleSnapshot.empty) {
          const roleDoc = roleSnapshot.docs[0];
          const roleId = roleDoc.data().roleId;
          const roleRef = await adminDb.collection('roles').doc(roleId).get();
          if (roleRef.exists) {
            role = roleRef.data()?.name || 'cashier';
          }
        }

        return {
          id: userDoc.id,
          name: user.name,
          email: user.email,
          branchId: user.branchId,
          role,
        };
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.id = user.id;
        token.branchId = user.branchId;
        token.role = user.role;
      }
      return token;
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.id as string;
        session.user.branchId = token.branchId as string;
        session.user.role = token.role as string;
      }
      return session;
    },
  },
  pages: {
    signIn: '/auth/login',
  },
  session: {
    strategy: 'jwt',
  },
  secret: process.env.NEXTAUTH_SECRET || 'super-secret-key-change-in-prod',
};
