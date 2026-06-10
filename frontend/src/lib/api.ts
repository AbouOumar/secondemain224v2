export type ApiCollection<T> = {
  data: T[];
  links?: unknown;
  meta?: unknown;
};

export type ApiResource<T> = {
  data: T;
};

export type Category = {
  id: number;
  libelle: string;
  slug: string;
  icon?: string | null;
  description?: string | null;
};

export type Article = {
  id: number;
  titre: string;
  slug: string;
  description: string;
  prix: number;
  currency: string;
  category?: Category | null;
  etat?: string | null;
  localisation: string;
  with_delivery: boolean;
  delivery_prix?: number | null;
  images?: { id?: number; url: string; ordre?: number }[];
  created_at?: string;
  is_published?: boolean;
};

export type User = {
  id: number;
  name: string;
  email?: string | null;
  phone: string;
  role: string;
  status: string;
  avatar?: string | null;
  created_at?: string;
};

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN_KEY = "secondmain224_token";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
  window.localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
  window.localStorage.removeItem(TOKEN_KEY);
}

export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = getToken();
  const headers = new Headers(init.headers);

  if (!(init.body instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }
  headers.set("Accept", "application/json");

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      payload?.message ?? "Une erreur est survenue.",
      response.status,
      payload?.errors ?? {},
    );
  }

  return payload as T;
}

export function firstImage(article: Article): string {
  return article.images?.[0]?.url ?? "/assets/img/photo/default.jpg";
}

export function formatPrice(value: number, currency = "GNF"): string {
  return `${Number(value).toLocaleString("fr-FR")} ${currency}`;
}

export function flattenErrors(error: unknown): Record<string, string> {
  if (error instanceof ApiError) {
    const result: Record<string, string> = {};
    Object.entries(error.errors).forEach(([key, messages]) => {
      result[key] = messages[0] ?? error.message;
    });
    return Object.keys(result).length ? result : { submit: error.message };
  }

  return {
    submit: error instanceof Error ? error.message : "Une erreur est survenue.",
  };
}
