"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, flattenErrors, getToken, type ApiCollection, type ApiResource, type Article, type Category } from "@/lib/api";

type FormState = {
  titre: string;
  description: string;
  prix: string;
  currency: string;
  category_id: string;
  localisation: string;
  etat: string;
  with_delivery: boolean;
  delivery_prix: string;
  images: File[];
};

const initialForm: FormState = {
  titre: "",
  description: "",
  prix: "",
  currency: "GNF",
  category_id: "",
  localisation: "",
  etat: "bon",
  with_delivery: false,
  delivery_prix: "",
  images: [],
};

const etats = [
  { value: "neuf", label: "Neuf" },
  { value: "tres_bon", label: "Très bon" },
  { value: "bon", label: "Bon" },
  { value: "moyen", label: "Moyen" },
];

export default function CreateArticlePage() {
  const router = useRouter();
  const [formData, setFormData] = useState<FormState>(initialForm);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    apiFetch<ApiCollection<Category>>("/categories")
      .then((payload) => setCategories(payload.data))
      .catch(() => setCategories([]));
  }, []);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setErrors({});

    if (!getToken()) {
      router.push("/login");
      return;
    }

    const body = new FormData();
    body.set("titre", formData.titre);
    body.set("description", formData.description);
    body.set("prix", formData.prix);
    body.set("currency", formData.currency);
    body.set("category_id", formData.category_id);
    body.set("localisation", formData.localisation);
    body.set("etat", formData.etat);
    body.set("with_delivery", formData.with_delivery ? "1" : "0");
    if (formData.delivery_prix) body.set("delivery_prix", formData.delivery_prix);
    formData.images.slice(0, 2).forEach((file) => body.append("images[]", file));

    try {
      const response = await apiFetch<ApiResource<Article>>("/articles", {
        method: "POST",
        body,
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
          <Link href="/articles" className="mb-6 inline-flex text-sm font-medium text-primary-600 hover:text-primary-500">
            Retour aux annonces
          </Link>
          <h1 className="text-2xl font-bold text-gray-900">Publier une annonce</h1>
          <p className="mt-2 text-gray-600">Les champs correspondent à la validation Laravel.</p>
        </div>

        <form className="rounded-lg bg-white p-6 shadow-sm" onSubmit={handleSubmit}>
          <div className="space-y-6">
            <div>
              <label htmlFor="titre" className="mb-2 block text-sm font-medium text-gray-700">Titre *</label>
              <input
                id="titre"
                value={formData.titre}
                onChange={(event) => setFormData({ ...formData, titre: event.target.value })}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              />
              {errors.titre && <p className="mt-1 text-sm text-red-600">{errors.titre}</p>}
            </div>

            <div>
              <label htmlFor="description" className="mb-2 block text-sm font-medium text-gray-700">Description *</label>
              <textarea
                id="description"
                rows={4}
                value={formData.description}
                onChange={(event) => setFormData({ ...formData, description: event.target.value })}
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
              />
              {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="prix" className="mb-2 block text-sm font-medium text-gray-700">Prix *</label>
                <input
                  id="prix"
                  type="number"
                  min="0"
                  value={formData.prix}
                  onChange={(event) => setFormData({ ...formData, prix: event.target.value })}
                  className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                />
                {errors.prix && <p className="mt-1 text-sm text-red-600">{errors.prix}</p>}
              </div>

              <div>
                <label htmlFor="category_id" className="mb-2 block text-sm font-medium text-gray-700">Catégorie *</label>
                <select
                  id="category_id"
                  value={formData.category_id}
                  onChange={(event) => setFormData({ ...formData, category_id: event.target.value })}
                  className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                >
                  <option value="">Sélectionnez une catégorie</option>
                  {categories.map((category) => (
                    <option key={category.id} value={category.id}>{category.libelle}</option>
                  ))}
                </select>
                {errors.category_id && <p className="mt-1 text-sm text-red-600">{errors.category_id}</p>}
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="localisation" className="mb-2 block text-sm font-medium text-gray-700">Localisation *</label>
                <input
                  id="localisation"
                  value={formData.localisation}
                  onChange={(event) => setFormData({ ...formData, localisation: event.target.value })}
                  className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                />
                {errors.localisation && <p className="mt-1 text-sm text-red-600">{errors.localisation}</p>}
              </div>

              <div>
                <label htmlFor="etat" className="mb-2 block text-sm font-medium text-gray-700">Etat</label>
                <select
                  id="etat"
                  value={formData.etat}
                  onChange={(event) => setFormData({ ...formData, etat: event.target.value })}
                  className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900"
                >
                  {etats.map((etat) => (
                    <option key={etat.value} value={etat.value}>{etat.label}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
                <input
                  type="checkbox"
                  checked={formData.with_delivery}
                  onChange={(event) => setFormData({ ...formData, with_delivery: event.target.checked })}
                />
                Livraison disponible
              </label>
              <input
                type="number"
                min="0"
                disabled={!formData.with_delivery}
                value={formData.delivery_prix}
                onChange={(event) => setFormData({ ...formData, delivery_prix: event.target.value })}
                placeholder="Prix livraison"
                className="block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 disabled:bg-gray-100"
              />
            </div>

            <div>
              <label htmlFor="images" className="mb-2 block text-sm font-medium text-gray-700">Photos (max 2 côté API)</label>
              <input
                id="images"
                type="file"
                accept="image/jpeg,image/png,image/jpg,image/webp"
                multiple
                onChange={(event) => setFormData({ ...formData, images: Array.from(event.target.files ?? []).slice(0, 2) })}
                className="block w-full text-sm text-gray-700"
              />
              {errors.images && <p className="mt-1 text-sm text-red-600">{errors.images}</p>}
            </div>
          </div>

          {errors.submit && <div className="mt-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">{errors.submit}</div>}

          <div className="mt-6 flex items-center justify-between">
            <Link href="/articles" className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
              Annuler
            </Link>
            <button
              type="submit"
              disabled={loading}
              className="rounded-md bg-primary-600 px-6 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
            >
              {loading ? "Publication..." : "Publier"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
