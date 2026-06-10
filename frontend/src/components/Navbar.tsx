"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

export default function Navbar() {
  const pathname = usePathname();

  return (
    <nav className="bg-white border-b border-gray-200 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex">
            {/* Logo */}
            <div className="flex-shrink-0 flex items-center">
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
            </div>
            {/* Navigation links */}
            <div className="hidden md:flex md:items-center md:space-x-8">
              <Link
                href="/"
                className={`
                  rounded-md px-3 py-2 text-sm font-medium
                  ${pathname === "/" ? "text-primary-600 border-b-2 border-primary-600" : "text-gray-500 hover:text-gray-700 hover:border-gray-300"}
                `}
              >
                Accueil
              </Link>
              <Link
                href="/articles"
                className={`
                  rounded-md px-3 py-2 text-sm font-medium
                  ${pathname === "/articles" ? "text-primary-600 border-b-2 border-primary-600" : "text-gray-500 hover:text-gray-700 hover:border-gray-300"}
                `}
              >
                Annonces
              </Link>
              <Link
                href="/contact"
                className={`
                  rounded-md px-3 py-2 text-sm font-medium
                  ${pathname === "/contact" ? "text-primary-600 border-b-2 border-primary-600" : "text-gray-500 hover:text-gray-700 hover:border-gray-300"}
                `}
              >
                Contact
              </Link>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            {/* Search - placeholder for now */}
            <div className="relative">
              <input
                type="text"
                placeholder="Rechercher..."
                className="w-64 rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>
            {/* User menu / Auth */}
            <div className="relative">
              <button
                className="flex items-center space-x-2 rounded-full border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <span className="h-6 w-6">
                  <img
                    src="/assets/img/apple-icon.png"
                    alt="User avatar"
                    className="rounded-full"
                  />
                </span>
                <span className="hidden md:block">Compte</span>
              </button>
              {/* Dropdown menu - simplified for now */}
              <div className="hidden md:block space-x-4">
                <Link href="/login" className="text-sm text-primary-600 hover:text-primary-500">
                  Se connecter
                </Link>
                <Link
                  href="/register"
                  className="text-sm text-primary-600 hover:text-primary-500 border border-primary-300 rounded px-2 py-1"
                >
                  S'inscrire
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
}
