import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch } from 'react-redux';
import { updateGameState, initializeGame } from './store/gameSlice';
import { GameAPI } from './services/api';
import { GameBoard } from './components/GameBoard';
import { sampleCards } from './data/sampleCards';
import {
  clearGuestSession,
  getFrontpageToken,
  getStoredGuestSession,
  saveGuestSession,
  useAuthStore,
} from './stores/authStore';
import './App.css';

const configuredLoginUrl = import.meta.env.VITE_WEB_HATCHERY_LOGIN_URL as string | undefined;

if (!configuredLoginUrl || configuredLoginUrl.trim().length === 0) {
  throw new Error('VITE_WEB_HATCHERY_LOGIN_URL is required');
}

const webHatcheryLoginUrl = configuredLoginUrl.trim();

function App() {
  const dispatch = useDispatch();
  const { user, authMode, loginUrl, setLoginUrl, login, logout } = useAuthStore();
  const [authReady, setAuthReady] = useState(false);
  const [authError, setAuthError] = useState<string | null>(null);
  const [gameReady, setGameReady] = useState(false);
  const [isStartingGuest, setIsStartingGuest] = useState(false);

  const resolvedLoginUrl = useMemo(() => {
    const baseLoginUrl = loginUrl ?? webHatcheryLoginUrl;

    const url = new URL(baseLoginUrl, window.location.origin);
    url.searchParams.set('return_to', window.location.href);

    if (user?.is_guest && user.id) {
      url.searchParams.set('link_guest', '1');
    }

    return url.toString();
  }, [loginUrl, user]);

  useEffect(() => {
    const bootstrapAuth = async () => {
      try {
        const params = new URLSearchParams(window.location.search);
        const frontpageToken = getFrontpageToken();
        const guestSession = getStoredGuestSession();

        if (frontpageToken && guestSession?.token) {
          const userFromLink = await GameAPI.linkGuestAccount(guestSession.token, frontpageToken);
          clearGuestSession();
          login(userFromLink, frontpageToken, 'frontpage');
          params.delete('link_guest');
          const nextQuery = params.toString();
          window.history.replaceState({}, document.title, `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`);
          setAuthError(null);
          setAuthReady(true);
          return;
        }

        if (guestSession?.token) {
          const guestUser = await GameAPI.getSession(guestSession.token);
          saveGuestSession({ token: guestSession.token, user: guestUser });
          login(guestUser, guestSession.token, 'guest');
          setAuthError(null);
          setAuthReady(true);
          return;
        }

        if (frontpageToken) {
          const frontpageUser = await GameAPI.getSession(frontpageToken);
          login(frontpageUser, frontpageToken, 'frontpage');
          setAuthError(null);
          setAuthReady(true);
          return;
        }

        setAuthReady(true);
      } catch (error) {
        console.error('Failed to initialize auth session:', error);
        clearGuestSession();
        logout();
        setAuthError(error instanceof Error ? error.message : 'Failed to initialize auth session');
        setAuthReady(true);
      }
    };

    bootstrapAuth();
  }, [login, logout]);

  useEffect(() => {
    const handleLoginRequired = (event: Event) => {
      const customEvent = event as CustomEvent<{ loginUrl?: string }>;
      if (customEvent.detail?.loginUrl) {
        setLoginUrl(customEvent.detail.loginUrl);
      }
    };

    window.addEventListener('webhatchery:login-required', handleLoginRequired as EventListener);
    return () => window.removeEventListener('webhatchery:login-required', handleLoginRequired as EventListener);
  }, [setLoginUrl]);

  useEffect(() => {
    if (!authReady || !user || gameReady) {
      return;
    }

    const initializeServerGame = async () => {
      try {
        const gameState = await GameAPI.createGame();
        dispatch(updateGameState(gameState));
        setGameReady(true);
      } catch (error) {
        console.error('Failed to create server-side game:', error);
        // Fallback to local initialization
        dispatch(
          initializeGame({
            gameId: 'game_' + Date.now(),
            cards: sampleCards,
          })
        );
        setGameReady(true);
      }
    };

    initializeServerGame();
  }, [authReady, dispatch, gameReady, user]);

  const handleGuestStart = async () => {
    try {
      setIsStartingGuest(true);
      setAuthError(null);
      const guestSession = await GameAPI.createGuestSession();
      saveGuestSession(guestSession);
      login(guestSession.user, guestSession.token, 'guest');
    } catch (error) {
      setAuthError(error instanceof Error ? error.message : 'Failed to start guest session');
    } finally {
      setIsStartingGuest(false);
    }
  };

  if (!authReady) {
    return (
      <div className="app-shell">
        <div className="auth-panel">
          <h1>Chyrralon</h1>
          <p>Checking your session.</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="app-shell">
        <div className="auth-panel">
          <h1>Chyrralon</h1>
          <p>Start with a guest creature pool or sign in with your WebHatchery account.</p>
          {authError ? <p className="auth-error">{authError}</p> : null}
          <div className="auth-actions">
            <button type="button" className="primary-action" onClick={handleGuestStart} disabled={isStartingGuest}>
              {isStartingGuest ? 'Starting Guest Session...' : 'Continue as Guest'}
            </button>
            {resolvedLoginUrl ? (
              <a className="secondary-action" href={resolvedLoginUrl}>
                Login with WebHatchery
              </a>
            ) : null}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="app-shell">
      <div className="app-topbar">
        <div>
          <span className="app-title">Chyrralon</span>
          <span className="app-status">
            {user.display_name || user.username || user.email || user.id}
            {authMode === 'guest' ? ' (Guest)' : ''}
          </span>
        </div>
        <div className="auth-actions">
          {user.is_guest && resolvedLoginUrl ? (
            <a className="secondary-action" href={resolvedLoginUrl}>
              Link Account
            </a>
          ) : null}
        </div>
      </div>
      <div className="App">
        <GameBoard />
      </div>
    </div>
  );
}

export default App;
