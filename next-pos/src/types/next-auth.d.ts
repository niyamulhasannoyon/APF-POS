import 'next-auth';

declare module 'next-auth' {
  interface Session {
    user: {
      id: string;
      name: string;
      email: string;
      branchId: string;
      role: string;
    };
  }

  interface User {
    branchId: string;
    role: string;
  }
}

declare module 'next-auth/jwt' {
  interface JWT {
    id: string;
    branchId: string;
    role: string;
  }
}
