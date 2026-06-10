"use client";

import Link from "next/link";
import { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiFetch, flattenErrors, getToken, type ApiResource, type Article } from "@/lib/api";

export default function EditArticlePage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const [formData, setFormData] = useState({
    titre: "",
    description: "",
    prix: "",
    localisation: "",
    etat: "bon",
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setErrors({});

    if (!getToken()) {
      router.push("/login");
      return;
    }

    try {
      const response = await apiFetch<ApiResource<Article>>(`/articles/${params.id}`, {
        method: "PUT",
        body: JSON.stringify({
          titre: formData.titre || undefined,
          description: formData.description || undefined,
          prix: formData.prix || undefined,
          localisation: formData.localisation || undefined,
          etat: formData.etat || undefined,
        }),
      });

      router.push(`/articles/${response.data.slug}`);
    } catch (error) {
      setErrors(flattenErrors(error));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mb-8">
          <Link href="/profile" className="mb-6 inline-flex text-sm font-medium text-primary-600 hover:text-primary-500">
            Retour au profil
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">Modifier l'annonce</h1>
          <p className="mt-2 text-gray-600">
            Renseignez uniquement les champs à modifier. L'API Laravel valide les noms `titre`, `prix`, `localisation` et `etat`.
          </p>
        </div>

        <form className="rounded-lg bg-white p-6 shadow-sm" onSubmit={handleSubmit}>
          <div className="space-y-6">
            <input
              value={formData.titre}
              onChange={(event) => setFormData({ ...formData, titre: event.target.value })}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              placeholder="Nouveau titre"
            />
            {errors.titre && <p className="text-sm text-red-600">{errors.titre}</p>}

            <textarea
              rows={4}
              value={formData.description}
              onChange={(event) => setFormData({ ...formData, description: event.target.value })}
              className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              placeholder="Nouvelle description"
            />
            {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}

            <div className="grid gap-4 sm:grid-cols-3">
              <input
                type="number"
                min="0"
                value={formData.prix}
                onChange={(event) => setFormData({ ...formData, prix: event.target.value })}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                placeholder="Prix"
              />
              <input
                value={formData.localisation}
                onChange={(event) => setFormData({ ...formData, localisation: event.target.value })}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                placeholder="Localisation"
              />
              <select
                value={formData.etat}
                onChange={(event) => setFormData({ ...formData, etat: event.target.value })}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              >
                <option value="neuf">Neuf</option>
                <option value="tres_bon">Très bon</option>
                <option value="bon">Bon</option>
                <option value="moyen">Moyen</option>
              </select>
            </div>
            {errors.submit && <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">{errors.submit}</div>}
          </div>

          <div className="mt-6 flex items-center justify-between">
            <Link href="/profile" className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
              Annuler
            </Link>
            <button
              type="submit"
              disabled={loading}
              className="rounded-md bg-primary-600 px-6 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
            >
              {loading ? "Mise à jour..." : "Mettre à jour"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
