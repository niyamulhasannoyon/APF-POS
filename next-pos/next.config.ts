import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Fix jose ESM module issue with jwks-rsa (used by next-auth)
  serverExternalPackages: ['jose', 'jwks-rsa'],
};

export default nextConfig;
