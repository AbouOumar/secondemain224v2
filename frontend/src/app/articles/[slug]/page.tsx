"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { apiFetch, firstImage, formatPrice, type ApiResource, type Article } from "@/lib/api";

export default function ArticleDetailPage() {
  const params = useParams<{ slug: string }>();
  const [article, setArticle] = useState<Article | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!params.slug) return;

    apiFetch<ApiResource<Article>>(`/articles/${params.slug}`)
      .then((payload) => setArticle(payload.data))
      .catch((err) => setError(err instanceof Error ? err.message : "Annonce introuvable."))
      .finally(() => setLoading(false));
  }, [params.slug]);

  if (loading) {
    return <div className="min-h-screen bg-gray-50 px-4 py-12 text-center text-gray-600">Chargement de l'annonce...</div>;
  }

  if (error || !article) {
    return (
      <div className="min-h-screen bg-gray-50 px-4 py-12">
        <div className="mx-auto max-w-xl rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
          {error ?? "Annonce introuvable."}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_420px] lg:px-8">
        <div>
          <Link href="/articles" className="mb-4 inline-block text-sm font-medium text-primary-600 hover:text-primary-500">
            Retour aux annonces
          </Link>
          <div className="relative aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
            <Image src={firstImage(article)} alt={article.titre} fill className="object-cover" unoptimized />
          </div>
          <div className="mt-6 rounded-lg bg-white p-6 shadow-sm">
            <h1 className="text-2xl font-bold text-gray-900">{article.titre}</h1>
            <p className="mt-4 whitespace-pre-line text-gray-700">{article.description}</p>
          </div>
        </div>

        <aside className="h-fit rounded-lg bg-white p-6 shadow-sm">
          <div className="text-3xl font-bold text-primary-600">{formatPrice(article.prix, article.currency)}</div>
          <dl className="mt-6 space-y-3 text-sm">
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">Catégorie</dt>
              <dd className="font-medium text-gray-900">{article.category?.libelle ?? "Non précisée"}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">Etat</dt>
              <dd className="font-medium text-gray-900">{article.etat ?? "Non précisé"}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">Localisation</dt>
              <dd className="font-medium text-gray-900">{article.localisation}</dd>
            </div>
            <div className="flex justify-between gap-4">
              <dt className="text-gray-500">Livraison</dt>
              <dd className="font-medium text-gray-900">
                {article.with_delivery ? formatPrice(article.delivery_prix ?? 0, article.currency) : "Non disponible"}
              </dd>
            </div>
          </dl>
        </aside>
      </div>
    </div>
  );
}
