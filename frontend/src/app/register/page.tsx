"use client";

import Link from "next/link";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, flattenErrors, setToken, type User } from "@/lib/api";

type RegisterResponse = {
  user: User;
  token: string;
};

const COUNTRY_CODES = [
  { code: "+224", flag: "🇬🇳", label: "Guinée" },
  { code: "+221", flag: "🇸🇳", label: "Sénégal" },
  { code: "+223", flag: "🇲🇱", label: "Mali" },
  { code: "+225", flag: "🇨🇮", label: "Côte d'Ivoire" },
  { code: "+232", flag: "🇸🇱", label: "Sierra Leone" },
  { code: "+231", flag: "🇱🇷", label: "Liberia" },
  { code: "+245", flag: "🇬🇼", label: "Guinée-Bissau" },
  { code: "+33", flag: "🇫🇷", label: "France" },
];

export default function RegisterPage() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "acheteur",
  });
  const [phoneCode, setPhoneCode] = useState(COUNTRY_CODES[0].code);
  const [phoneNumber, setPhoneNumber] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await apiFetch<RegisterResponse>("/auth/register", {
        method: "POST",
        body: JSON.stringify({
          ...formData,
          phone: phoneNumber ? `${phoneCode}${phoneNumber.replace(/\s+/g, "")}` : "",
        }),
      });

      setToken(response.token);
      router.push("/profile");
    } catch (error) {
      setErrors(flattenErrors(error));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">Créer un compte</h2>
          <p className="mt-2 text-center text-sm text-gray-600">Le téléphone est requis par le backend.</p>
        </div>

        <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
          <input
            name="name"
            value={formData.name}
            onChange={(event) => setFormData({ ...formData, name: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
            placeholder="Nom complet"
          />
          {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}

          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={(event) => setFormData({ ...formData, email: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
            placeholder="Email"
          />
          {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}

          <div className="flex gap-2">
            <select
              value={phoneCode}
              onChange={(event) => setPhoneCode(event.target.value)}
              className="rounded-md border border-gray-300 px-2 py-2 text-gray-900"
              aria-label="Indicatif pays"
            >
              {COUNTRY_CODES.map((country) => (
                <option key={country.code} value={country.code}>
                  {country.flag} {country.code}
                </option>
              ))}
            </select>
            <input
              type="tel"
              required
              value={phoneNumber}
              onChange={(event) => setPhoneNumber(event.target.value)}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              placeholder="6XX XX XX XX"
            />
          </div>
          {errors.phone && <p className="text-sm text-red-600">{errors.phone}</p>}

          <select
            name="role"
            value={formData.role}
            onChange={(event) => setFormData({ ...formData, role: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
          >
            <option value="acheteur">Acheteur</option>
            <option value="vendeur">Vendeur</option>
            <option value="revendeur_pro">Revendeur pro</option>
            <option value="motard">Motard</option>
          </select>

          <div className="relative">
            <input
              type={showPassword ? "text" : "password"}
              name="password"
              value={formData.password}
              onChange={(event) => setFormData({ ...formData, password: event.target.value })}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-gray-900"
              placeholder="Mot de passe, min. 8 caractères"
            />
            <button
              type="button"
              onClick={() => setShowPassword((current) => !current)}
              className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700"
              aria-label="Afficher/masquer le mot de passe"
            >
              {showPassword ? "🙈" : "👁️"}
            </button>
          </div>
          {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}

          <div className="relative">
            <input
              type={showPasswordConfirmation ? "text" : "password"}
              name="password_confirmation"
              value={formData.password_confirmation}
              onChange={(event) => setFormData({ ...formData, password_confirmation: event.target.value })}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-gray-900"
              placeholder="Confirmer le mot de passe"
            />
            <button
              type="button"
              onClick={() => setShowPasswordConfirmation((current) => !current)}
              className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700"
              aria-label="Afficher/masquer le mot de passe"
            >
              {showPasswordConfirmation ? "🙈" : "👁️"}
            </button>
          </div>
          {errors.password_confirmation && <p className="text-sm text-red-600">{errors.password_confirmation}</p>}
          {errors.submit && <p className="text-sm text-red-600">{errors.submit}</p>}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-60"
          >
            {loading ? "Création..." : "Créer mon compte"}
          </button>
        </form>

        <div className="text-center">
          <p className="text-sm text-gray-500">Vous avez déjà un compte ?</p>
          <Link href="/login" className="font-medium text-primary-600 hover:text-primary-500">
            Connectez-vous
          </Link>
        </div>
      </div>
    </div>
  );
}
