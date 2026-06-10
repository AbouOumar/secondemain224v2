"use client";

import Link from "next/link";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, flattenErrors, setToken, type User } from "@/lib/api";

type AuthResponse = {
  user: User;
  token: string;
};

export default function LoginPage() {
  const router = useRouter();
  const [login, setLogin] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await apiFetch<AuthResponse>("/auth/login", {
        method: "POST",
        body: JSON.stringify({ login, password }),
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
          <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">Se connecter</h2>
          <p className="mt-2 text-center text-sm text-gray-600">Email ou téléphone, comme attendu par l'API Laravel.</p>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <input
            type="text"
            name="login"
            autoComplete="username"
            required
            value={login}
            onChange={(event) => setLogin(event.target.value)}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 shadow-sm"
            placeholder="Email ou téléphone"
          />
          {errors.login && <p className="text-sm text-red-600">{errors.login}</p>}

          <input
            type="password"
            name="password"
            autoComplete="current-password"
            required
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 shadow-sm"
            placeholder="Mot de passe"
          />
          {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
          {errors.submit && <p className="text-sm text-red-600">{errors.submit}</p>}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-60"
          >
            {loading ? "Connexion..." : "Se connecter"}
          </button>
        </form>

        <div className="text-center">
          <p className="text-sm text-gray-500">Vous n'avez pas de compte ?</p>
          <Link href="/register" className="font-medium text-primary-600 hover:text-primary-500">
            Inscrivez-vous
          </Link>
        </div>
      </div>
    </div>
  );
}
