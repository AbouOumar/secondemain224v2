"use client";

import Link from "next/link";
import Image from "next/image";
import { useEffect, useMemo, useState } from "react";
import { apiFetch, type ApiCollection, type Article, type Category, firstImage, formatPrice } from "@/lib/api";

export default function ArticlesPage() {
  const [articles, setArticles] = useState<Article[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState({
    search: "",
    category_id: "",
    prix_min: "",
    prix_max: "",
    localisation: "",
    sort: "latest",
  });

  useEffect(() => {
    apiFetch<ApiCollection<Category>>("/categories")
      .then((payload) => setCategories(payload.data))
      .catch(() => setCategories([]));
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value && key !== "sort") params.set(key, value);
    });

    queueMicrotask(() => {
      setLoading(true);
      setError(null);
    });

    apiFetch<ApiCollection<Article>>(`/articles?${params.toString()}`, {
      signal: controller.signal,
    })
      .then((payload) => setArticles(payload.data))
      .catch((err) => {
        if ((err as Error).name !== "AbortError") {
          setError(err instanceof Error ? err.message : "Impossible de charger les annonces.");
        }
      })
      .finally(() => setLoading(false));

    return () => controller.abort();
  }, [filters]);

  const sortedArticles = useMemo(() => {
    return [...articles].sort((a, b) => {
      if (filters.sort === "price-low") return a.prix - b.prix;
      if (filters.sort === "price-high") return b.prix - a.prix;
      if (filters.sort === "oldest") {
        return new Date(a.created_at ?? 0).getTime() - new Date(b.created_at ?? 0).getTime();
      }
      return new Date(b.created_at ?? 0).getTime() - new Date(a.created_at ?? 0).getTime();
    });
  }, [articles, filters.sort]);

  const handleFilterChange = (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = event.target;
    setFilters((current) => ({ ...current, [name]: value }));
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Toutes les annonces</h1>
            <p className="mt-1 text-sm text-gray-600">Les annonces publiées depuis l'API Laravel.</p>
          </div>
          <Link
            href="/articles/create"
            className="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700"
          >
            Publier une annonce
          </Link>
        </div>

        <form className="mb-8 grid gap-4 rounded-lg bg-white p-4 shadow-sm md:grid-cols-3 lg:grid-cols-6">
          <input
            name="search"
            value={filters.search}
            onChange={handleFilterChange}
            placeholder="Recherche"
            className="rounded-md border border-gray-300 px-3 py-2 text-sm md:col-span-2"
          />
          <select
            name="category_id"
            value={filters.category_id}
            onChange={handleFilterChange}
            className="rounded-md border border-gray-300 px-3 py-2 text-sm"
          >
            <option value="">Toutes catégories</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.libelle}
              </option>
            ))}
          </select>
          <input
            name="localisation"
            value={filters.localisation}
            onChange={handleFilterChange}
            placeholder="Localisation"
            className="rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
          <input
            name="prix_min"
            value={filters.prix_min}
            onChange={handleFilterChange}
            type="number"
            min="0"
            placeholder="Prix min"
            className="rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
          <input
            name="prix_max"
            value={filters.prix_max}
            onChange={handleFilterChange}
            type="number"
            min="0"
            placeholder="Prix max"
            className="rounded-md border border-gray-300 px-3 py-2 text-sm"
          />
          <select
            name="sort"
            value={filters.sort}
            onChange={handleFilterChange}
            className="rounded-md border border-gray-300 px-3 py-2 text-sm"
          >
            <option value="latest">Plus récentes</option>
            <option value="oldest">Plus anciennes</option>
            <option value="price-low">Prix croissant</option>
            <option value="price-high">Prix décroissant</option>
          </select>
        </form>

        <div className="mb-6 text-sm text-gray-500">
          {loading ? "Chargement..." : `${sortedArticles.length} annonce${sortedArticles.length > 1 ? "s" : ""}`}
        </div>

        {error && <div className="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {sortedArticles.map((article) => (
            <Link key={article.id} href={`/articles/${article.slug}`} className="group">
              <div className="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                <div className="relative aspect-[4/3] bg-gray-100">
                  <Image src={firstImage(article)} alt={article.titre} fill className="object-cover" unoptimized />
                  <div className="absolute bottom-0 left-0 right-0 bg-black/50 px-3 py-2 text-sm text-white">
                    {article.localisation}
                  </div>
                </div>
                <div className="flex flex-1 flex-col p-4">
                  <h3 className="mb-2 line-clamp-2 text-lg font-semibold text-gray-900 group-hover:text-primary-600">
                    {article.titre}
                  </h3>
                  <p className="flex-1 text-sm text-gray-500">
                    {article.category?.libelle ?? "Sans catégorie"} · {article.etat ?? "Etat non précisé"}
                  </p>
                  <div className="mt-4 text-xl font-bold text-primary-600">{formatPrice(article.prix, article.currency)}</div>
                </div>
              </div>
            </Link>
          ))}

          {!loading && sortedArticles.length === 0 && (
            <div className="col-span-full rounded-lg bg-white py-12 text-center text-gray-500">
              Aucune annonce ne correspond aux filtres.
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
