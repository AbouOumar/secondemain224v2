"use client";

import { useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { apiFetch, flattenErrors, setToken, type User } from "@/lib/api";

type AuthResponse = {
  user: User;
  token: string;
};

type GoogleCredentialResponse = {
  credential: string;
};

type GoogleAccountsId = {
  initialize: (config: {
    client_id: string;
    callback: (response: GoogleCredentialResponse) => void;
  }) => void;
  renderButton: (parent: HTMLElement, options: Record<string, unknown>) => void;
};

declare global {
  interface Window {
    google?: { accounts: { id: GoogleAccountsId } };
  }
}

const GOOGLE_CLIENT_ID = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
const GSI_SCRIPT_SRC = "https://accounts.google.com/gsi/client";

let gsiScriptPromise: Promise<void> | null = null;

/**
 * Charge une seule fois le script Google Identity Services.
 */
function loadGsiScript(): Promise<void> {
  if (window.google?.accounts?.id) return Promise.resolve();

  gsiScriptPromise ??= new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = GSI_SCRIPT_SRC;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => {
      gsiScriptPromise = null;
      reject(new Error("Impossible de charger Google."));
    };
    document.head.appendChild(script);
  });

  return gsiScriptPromise;
}

/**
 * Bouton « Continuer avec Google » : Google renvoie un ID token, que l'API
 * Laravel vérifie avant de connecter (ou créer) le compte.
 */
export default function GoogleSignInButton({ text = "continue_with" }: { text?: "continue_with" | "signup_with" | "signin_with" }) {
  const router = useRouter();
  const containerRef = useRef<HTMLDivElement>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!GOOGLE_CLIENT_ID) return;

    let cancelled = false;

    const handleCredential = async ({ credential }: GoogleCredentialResponse) => {
      setLoading(true);
      setError(null);

      try {
        const response = await apiFetch<AuthResponse>("/auth/oauth/google", {
          method: "POST",
          body: JSON.stringify({ token: credential }),
        });

        setToken(response.token);
        router.push("/profile");
      } catch (err) {
        const errors = flattenErrors(err);
        setError(errors.submit ?? Object.values(errors)[0] ?? "Connexion avec Google échouée.");
      } finally {
        setLoading(false);
      }
    };

    loadGsiScript()
      .then(() => {
        if (cancelled || !containerRef.current || !window.google) return;

        window.google.accounts.id.initialize({
          client_id: GOOGLE_CLIENT_ID,
          callback: handleCredential,
        });
        window.google.accounts.id.renderButton(containerRef.current, {
          theme: "outline",
          size: "large",
          shape: "pill",
          text,
          locale: "fr",
          width: containerRef.current.offsetWidth || 320,
        });
      })
      .catch((err: Error) => {
        if (!cancelled) setError(err.message);
      });

    return () => {
      cancelled = true;
    };
  }, [router, text]);

  if (!GOOGLE_CLIENT_ID) return null;

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-3 text-sm text-gray-500">
        <span className="h-px flex-1 bg-gray-200" />
        ou
        <span className="h-px flex-1 bg-gray-200" />
      </div>
      <div ref={containerRef} className={`flex w-full justify-center ${loading ? "pointer-events-none opacity-60" : ""}`} />
      {loading && <p className="text-center text-sm text-gray-500">Connexion avec Google...</p>}
      {error && <p className="text-center text-sm text-red-600">{error}</p>}
    </div>
  );
}
