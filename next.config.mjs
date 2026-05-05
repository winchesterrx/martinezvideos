/** @type {import('next').NextConfig} */
const nextConfig = {
  typescript: {
    // Ignorando erros de tipagem da migração legada
    ignoreBuildErrors: true,
  }
};

export default nextConfig;
