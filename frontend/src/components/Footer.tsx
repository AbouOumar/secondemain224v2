import Link from "next/link";

export default function Footer() {
  return (
    <footer className="border-t border-gray-200 bg-gray-50">
      <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col sm:flex-row sm:justify-between">
          <div className="flex-1 flex flex-col items-center sm:items-start sm:mb-0 mb-6">
            <Link href="/" className="flex items-center space-x-3 rtl:space-x-reverse">
              <span className="h-8 w-auto">
                <img
                  src="/assets/img/logo.png"
                  alt="Seconde Main 224"
                  className="h-8 w-auto"
                />
              </span>
              <span className="self-center text-xl font-semibold whitespace-nowrap text-primary-600">
                Seconde Main 224
              </span>
            </Link>
            <p className="mt-4 text-center text-sm text-gray-500 sm:text-left">
              Marketplace de biens d'occasion en Guinée &bull; 2026
            </p>
          </div>

          <div className="flex-1 flex flex-col items-center sm:items-start md:mt-0 mt-8 space-y-4">
            <h2 className="text-lg font-semibold text-gray-900 mb-2">Liens rapides</h2>
            <div className="space-y-2">
              <Link href="/" className="text-sm text-gray-600 hover:text-primary-600">
                Accueil
              </Link>
              <Link href="/nous" className="text-sm text-gray-600 hover:text-primary-600">
                Nous
              </Link>
              <Link href="/contact" className="text-sm text-gray-600 hover:text-primary-600">
                Contact
              </Link>
              <Link href="/login" className="text-sm text-gray-600 hover:text-primary-600">
                Se connecter
              </Link>
              <Link href="/register" className="text-sm text-gray-600 hover:text-primary-600">
                S'inscrire
              </Link>
            </div>
          </div>

          <div className="flex-1 flex flex-col items-center sm:items-start md:mt-0 mt-8 space-y-4">
            <h2 className="text-lg font-semibold text-gray-900 mb-2">Suivez-nous</h2>
            <div className="flex space-x-4">
              <a href="#" className="text-gray-600 hover:text-primary-600">
                {/* In a real app, we'd use actual social media links */}
                <span className="text-2xl">📱</span>
              </a>
              <a href="#" className="text-gray-600 hover:text-primary-600">
                <span className="text-2xl">📘</span>
              </a>
              <a href="#" className="text-gray-600 hover:text-primary-600">
                <span className="text-2xl">📸</span>
              </a>
              <a href="#" className="text-gray-600 hover:text-primary-600">
                <span className="text-2xl">🐦</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}