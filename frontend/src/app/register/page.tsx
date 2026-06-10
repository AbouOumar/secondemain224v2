"use client";

import Link from "next/link";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, flattenErrors, setToken, type User } from "@/lib/api";

type RegisterResponse = {
  user: User;
  token: string;
};

export default function RegisterPage() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
    role: "acheteur",
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await apiFetch<RegisterResponse>("/auth/register", {
        method: "POST",
        body: JSON.stringify(formData),
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

          <input
            type="tel"
            name="phone"
            required
            value={formData.phone}
            onChange={(event) => setFormData({ ...formData, phone: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
            placeholder="+224 6XX XX XX XX"
          />
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

          <input
            type="password"
            name="password"
            value={formData.password}
            onChange={(event) => setFormData({ ...formData, password: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
            placeholder="Mot de passe, min. 8 caractères"
          />
          {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}

          <input
            type="password"
            name="password_confirmation"
            value={formData.password_confirmation}
            onChange={(event) => setFormData({ ...formData, password_confirmation: event.target.value })}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
            placeholder="Confirmer le mot de passe"
          />
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
