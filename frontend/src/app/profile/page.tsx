"use client";

import Link from "next/link";
import Image from "next/image";
import { useEffect, useState, useRef } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, clearToken, firstImage, formatPrice, getToken, flattenErrors, type ApiCollection, type ApiResource, type Article, type User } from "@/lib/api";

type Stats = {
  total: number;
  en_vente: number;
  vendus: number;
  revenus: number;
};

export default function ProfileDashboard() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [listings, setListings] = useState<Article[]>([]);
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("dashboard");
  const [error, setError] = useState<string | null>(null);

  // Form states
  const [profileData, setProfileData] = useState({
    name: "",
    email: "",
    phone: "",
  });
  const [passwordData, setPasswordData] = useState({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
  });
  const [formLoading, setFormLoading] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (!getToken()) {
      router.push("/login");
      return;
    }

    fetchData();
  }, [router]);

  const fetchData = () => {
    setLoading(true);
    Promise.all([
      apiFetch<ApiResource<User>>("/profile"),
      apiFetch<ApiCollection<Article>>("/seller/articles"),
      apiFetch<Stats>("/seller/stats").catch(() => null),
    ])
      .then(([profile, articles, sellerStats]) => {
        setUser(profile.data);
        setProfileData({
          name: profile.data.name,
          email: profile.data.email ?? "",
          phone: profile.data.phone,
        });
        setListings(articles.data);
        setStats(sellerStats);
      })
      .catch((err) => {
        if (err instanceof Error && err.message.includes("Unauthenticated")) {
          clearToken();
          router.push("/login");
          return;
        }
        setError(err instanceof Error ? err.message : "Impossible de charger le profil.");
      })
      .finally(() => setLoading(false));
  };

  const handleProfileUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormLoading(true);
    setFormErrors({});
    setSuccessMessage(null);

    try {
      const response = await apiFetch<ApiResource<User>>("/profile", {
        method: "PUT",
        body: JSON.stringify(profileData),
      });
      setUser(response.data);
      setSuccessMessage("Profil mis à jour avec succès !");
    } catch (err) {
      setFormErrors(flattenErrors(err));
    } finally {
      setFormLoading(false);
    }
  };

  const handlePasswordUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormLoading(true);
    setFormErrors({});
    setSuccessMessage(null);

    try {
      await apiFetch("/profile", {
        method: "PUT",
        body: JSON.stringify({
          ...profileData,
          current_password: passwordData.current_password,
          new_password: passwordData.new_password,
          new_password_confirmation: passwordData.new_password_confirmation,
        }),
      });
      setPasswordData({
        current_password: "",
        new_password: "",
        new_password_confirmation: "",
      });
      setSuccessMessage("Mot de passe mis à jour avec succès !");
    } catch (err) {
      setFormErrors(flattenErrors(err));
    } finally {
      setFormLoading(false);
    }
  };

  const handleAvatarChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("avatar", file);

    setFormLoading(true);
    setFormErrors({});
    setSuccessMessage(null);

    try {
      const response = await apiFetch<{ avatar: string }>("/profile/avatar", {
        method: "POST",
        body: formData,
      });
      if (user) {
        setUser({ ...user, avatar: response.avatar });
      }
      setSuccessMessage("Photo de profil mise à jour !");
    } catch (err) {
      setFormErrors(flattenErrors(err));
    } finally {
      setFormLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50 py-12">
        <div className="text-center">
          <div className="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-t-2 border-primary-600" />
          <p className="mt-2 text-gray-600">Chargement du profil...</p>
        </div>
      </div>
    );
  }

  if (error || !user) {
    return (
      <div className="min-h-screen bg-gray-50 px-4 py-12">
        <div className="mx-auto max-w-xl rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
          {error ?? "Profil indisponible."}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 pb-12">
      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8 rounded-lg bg-white p-6 shadow-sm">
          <div className="flex flex-col items-center gap-6 sm:flex-row sm:text-left text-center">
            <div className="relative group cursor-pointer" onClick={() => fileInputRef.current?.click()}>
              <div className="h-24 w-24 overflow-hidden rounded-full bg-gray-100 ring-4 ring-white shadow-md">
                <Image 
                  src={user.avatar?.startsWith('http') ? user.avatar : (user.avatar ? `http://127.0.0.1:8000/storage/${user.avatar}` : "/assets/img/apple-icon.png")} 
                  alt="Avatar utilisateur" 
                  fill 
                  className="object-cover" 
                  unoptimized 
                />
              </div>
              <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 transition-opacity">
                <span className="text-white text-xs font-medium">Changer</span>
              </div>
              <input 
                type="file" 
                ref={fileInputRef} 
                className="hidden" 
                accept="image/*" 
                onChange={handleAvatarChange}
                disabled={formLoading}
              />
            </div>
            <div className="flex-1">
              <h1 className="text-2xl font-bold text-gray-900">{user.name}</h1>
              <p className="mt-1 text-sm text-gray-600">{user.email ?? user.phone}</p>
              <div className="mt-2 flex flex-wrap justify-center sm:justify-start gap-2">
                <span className="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">
                  {user.role}
                </span>
                {user.status && (
                  <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                    {user.status}
                  </span>
                )}
              </div>
            </div>
            <div className="flex gap-3">
              <Link href="/articles/create" className="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 shadow-sm transition-colors">
                Vendre un article
              </Link>
              <button
                type="button"
                onClick={() => {
                  clearToken();
                  router.push("/login");
                }}
                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors"
              >
                Déconnexion
              </button>
            </div>
          </div>
        </div>

        <div className="rounded-lg bg-white shadow-sm overflow-hidden">
          <div className="flex border-b border-gray-200 overflow-x-auto">
            <button
              type="button"
              onClick={() => setActiveTab("dashboard")}
              className={`flex-1 min-w-[120px] px-4 py-3 text-center text-sm font-medium transition-colors ${activeTab === "dashboard" ? "border-b-2 border-primary-600 text-primary-600 bg-primary-50/50" : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"}`}
            >
              Tableau de bord
            </button>
            <button
              type="button"
              onClick={() => setActiveTab("listings")}
              className={`flex-1 min-w-[120px] px-4 py-3 text-center text-sm font-medium transition-colors ${activeTab === "listings" ? "border-b-2 border-primary-600 text-primary-600 bg-primary-50/50" : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"}`}
            >
              Mes annonces ({listings.length})
            </button>
            <button
              type="button"
              onClick={() => setActiveTab("settings")}
              className={`flex-1 min-w-[120px] px-4 py-3 text-center text-sm font-medium transition-colors ${activeTab === "settings" ? "border-b-2 border-primary-600 text-primary-600 bg-primary-50/50" : "text-gray-500 hover:text-gray-700 hover:bg-gray-50"}`}
            >
              Paramètres
            </button>
          </div>

          <div className="p-6">
            {successMessage && (
              <div className="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                {successMessage}
              </div>
            )}

            {activeTab === "dashboard" && (
              <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <Stat label="Total annonces" value={stats?.total ?? listings.length} />
                <Stat label="En vente" value={stats?.en_vente ?? listings.filter((article) => article.is_published).length} />
                <Stat label="Vendus" value={stats?.vendus ?? 0} />
                <Stat label="Revenus" value={formatPrice(stats?.revenus ?? 0, "GNF")} />
              </div>
            )}

            {activeTab === "listings" && (
              <div className="space-y-4">
                {listings.map((article) => (
                  <Link key={article.id} href={`/articles/${article.slug}`} className="block">
                    <div className="flex items-center rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-primary-200">
                      <div className="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                        <Image src={firstImage(article)} alt={article.titre} fill className="object-cover" unoptimized />
                      </div>
                      <div className="ml-4 flex-1">
                        <h3 className="text-lg font-semibold text-gray-900 line-clamp-1">{article.titre}</h3>
                        <p className="text-sm text-gray-500">{formatPrice(article.prix, article.currency)} · {article.localisation}</p>
                      </div>
                      <div className="ml-4">
                        <span className={`text-xs px-2 py-1 rounded-full font-medium ${article.is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                          {article.is_published ? 'En ligne' : 'Brouillon'}
                        </span>
                      </div>
                    </div>
                  </Link>
                ))}

                {listings.length === 0 && (
                  <div className="py-12 text-center">
                    <p className="text-gray-500">Vous n'avez pas encore publié d'annonces.</p>
                    <Link href="/articles/create" className="mt-4 inline-block rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                      Publier votre première annonce
                    </Link>
                  </div>
                )}
              </div>
            )}

            {activeTab === "settings" && (
              <div className="max-w-2xl mx-auto space-y-10">
                {/* Profile Information */}
                <section>
                  <h2 className="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Informations personnelles</h2>
                  <form onSubmit={handleProfileUpdate} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Nom complet</label>
                        <input
                          type="text"
                          value={profileData.name}
                          onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                          className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        {formErrors.name && <p className="text-xs text-red-600">{formErrors.name}</p>}
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Téléphone</label>
                        <input
                          type="text"
                          value={profileData.phone}
                          onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                          className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        {formErrors.phone && <p className="text-xs text-red-600">{formErrors.phone}</p>}
                      </div>
                    </div>
                    <div className="space-y-1">
                      <label className="text-sm font-medium text-gray-700">Email</label>
                      <input
                        type="email"
                        value={profileData.email}
                        onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                        className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                      />
                      {formErrors.email && <p className="text-xs text-red-600">{formErrors.email}</p>}
                    </div>
                    <button
                      type="submit"
                      disabled={formLoading}
                      className="inline-flex justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
                    >
                      Enregistrer les modifications
                    </button>
                  </form>
                </section>

                {/* Password Change */}
                <section>
                  <h2 className="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Changer le mot de passe</h2>
                  <form onSubmit={handlePasswordUpdate} className="space-y-4">
                    <div className="space-y-1">
                      <label className="text-sm font-medium text-gray-700">Mot de passe actuel</label>
                      <input
                        type="password"
                        value={passwordData.current_password}
                        onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                        className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                      />
                      {formErrors.current_password && <p className="text-xs text-red-600">{formErrors.current_password}</p>}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                        <input
                          type="password"
                          value={passwordData.new_password}
                          onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
                          className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        {formErrors.new_password && <p className="text-xs text-red-600">{formErrors.new_password}</p>}
                      </div>
                      <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Confirmer le nouveau mot de passe</label>
                        <input
                          type="password"
                          value={passwordData.new_password_confirmation}
                          onChange={(e) => setPasswordData({ ...passwordData, new_password_confirmation: e.target.value })}
                          className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                      </div>
                    </div>
                    <button
                      type="submit"
                      disabled={formLoading}
                      className="inline-flex justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
                    >
                      Mettre à jour le mot de passe
                    </button>
                  </form>
                </section>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function Stat({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4 text-center transition-shadow hover:shadow-md">
      <h2 className="text-sm font-medium text-gray-600">{label}</h2>
      <p className="mt-2 text-2xl font-bold text-primary-600">{value}</p>
    </div>
  );
}
