import Link from "next/link";
import Image from "next/image";
import { apiFetch, type Article, type Category, type ApiCollection, firstImage, formatPrice } from "@/lib/api";

export default async function Home() {
  let categories: Category[] = [];
  let featuredArticles: Article[] = [];

  try {
    const [categoriesData, articlesData] = await Promise.all([
      apiFetch<ApiCollection<Category>>("/categories"),
      apiFetch<ApiCollection<Article>>("/articles")
    ]);
    categories = categoriesData.data;
    featuredArticles = articlesData.data.slice(0, 3);
  } catch (error) {
    console.error("Failed to fetch data:", error);
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="relative bg-[url('/assets/img/hero-bg.jpg')] bg-center bg-cover h-[600px] flex items-center">
        <div className="absolute inset-0 bg-black bg-opacity-40"></div>
        <div className="relative z-10 max-w-4xl mx-auto px-6 text-center text-white">
          <h1 className="display-font mb-4 text-4xl font-bold md:text-5xl lg:text-6xl">
            Trouvez des bonnes affaires en Guinée
          </h1>
          <p className="mb-6 text-lg md:text-xl lg:text-2xl max-w-xl mx-auto">
            Achetez et vendez des biens d'occasion de qualité près de chez vous
          </p>
          {/* Search Form */}
          <form
            action="/articles"
            method="GET"
            className="flex flex-col lg:flex-row items-stretch gap-4 max-w-xl mx-auto"
          >
            <input
              name="search"
              type="text"
              placeholder="Rechercher des articles..."
              className="flex-1 min-w-0 rounded-l-lg border border-gray-300 px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white text-gray-900"
            />
            <button
              type="submit"
              className="flex-1 rounded-r-lg bg-primary-600 px-6 py-3 text-lg font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-300"
            >
              Rechercher
            </button>
          </form>
        </div>
      </section>

      {/* Categories Section */}
      <section className="py-12 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-2xl font-bold text-gray-900 mb-8 text-center">
            Parcourir par catégorie
          </h2>
          <div className="grid grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {categories.length > 0 ? (
              categories.map((category) => (
                <Link
                  key={category.id}
                  href={`/articles?category_id=${category.id}`}
                  className="group"
                >
                  <div
                    className="relative aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-lg bg-gray-50 group-hover:bg-primary-50 transition-colors border border-gray-100 p-6 flex flex-col items-center justify-center text-center"
                  >
                    <div className="mb-4 text-4xl text-primary-600">
                      <i className={`bx ${category.icon || 'bx-category'}`}></i>
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 group-hover:text-primary-600">
                      {category.libelle}
                    </h3>
                  </div>
                </Link>
              ))
            ) : (
              <p className="col-span-full text-center text-gray-500">Aucune catégorie disponible.</p>
            )}
          </div>
        </div>
      </section>

      {/* Featured Articles Section */}
      <section className="py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center mb-8">
            <h2 className="text-2xl font-bold text-gray-900">
              Dernières annonces
            </h2>
            <Link
              href="/articles"
              className="text-sm text-primary-600 hover:text-primary-500 font-medium"
            >
              Voir toutes les annonces →
            </Link>
          </div>
          <div className="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {featuredArticles.length > 0 ? (
              featuredArticles.map((article) => (
                <Link
                  key={article.id}
                  href={`/articles/${article.slug}`}
                  className="group"
                >
                  <div
                    className="flex flex-col h-full bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300"
                  >
                    <div className="relative aspect-[4/3] w-full">
                      <Image
                        src={firstImage(article)}
                        alt={article.titre}
                        fill
                        className="object-cover"
                      />
                      <div className="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white px-3 py-2 text-sm">
                        {article.localisation}
                      </div>
                    </div>
                    <div className="flex-1 flex flex-col p-4">
                      <h3 className="mb-2 line-clamp-2 text-lg font-semibold text-gray-900 group-hover:text-primary-600">
                        {article.titre}
                      </h3>
                      <p className="flex-1 text-gray-500">
                        {article.category?.libelle} • {article.localisation}
                      </p>
                      <div className="mt-4 flex items-baseline">
                        <span className="text-xl font-bold text-primary-600">
                          {formatPrice(article.prix, article.currency)}
                        </span>
                      </div>
                    </div>
                  </div>
                </Link>
              ))
            ) : (
              <p className="col-span-full text-center text-gray-500">Aucune annonce disponible pour le moment.</p>
            )}
          </div>
        </div>
      </section>

      {/* How it works section */}
      <section className="py-12 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-2xl font-bold text-gray-900 text-center mb-8">
            Comment ça marche ?
          </h2>
          <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 text-center">
            <div>
              <div className="flex items-center justify-center mb-4 mx-auto w-12 h-12 bg-primary-50 rounded-full">
                <svg className="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3" />
                </svg>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Publier une annonce</h3>
              <p className="text-gray-600">
                Créez votre annonce en quelques minutes avec des photos et une description détaillée.
              </p>
            </div>
            <div>
              <div className="flex items-center justify-center mb-4 mx-auto w-12 h-12 bg-primary-50 rounded-full">
                <svg className="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3" />
                </svg>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Trouver ce que vous cherchez</h3>
              <p className="text-gray-600">
                Utilisez notre recherche avancée pour filtrer par catégorie, prix, localisation et plus encore.
              </p>
            </div>
            <div>
              <div className="flex items-center justify-center mb-4 mx-auto w-12 h-12 bg-primary-50 rounded-full">
                <svg className="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3" />
                </svg>
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Contacter le vendeur</h3>
              <p className="text-gray-600">
                Discutez directement avec le vendeur via notre messagerie sécurisée pour négocier et conclure l'affaire.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Call to action section */}
      <section className="py-16 bg-primary-50">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">
            Prêt à vendre vos objets inutilisés ?
          </h2>
          <p className="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Transformez vos affaires en argent liquide en quelques clics. C'est simple, rapide et gratuit !
          </p>
          <Link
            href="/articles/create"
            className="inline-block bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition-colors duration-300"
          >
            Publier une annonce gratuite
          </Link>
        </div>
      </section>
    </div>
  );
}