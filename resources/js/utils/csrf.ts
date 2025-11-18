// Helper para obtener el token CSRF
export function getCsrfToken(): string | null {
    // Primero intentar obtener del meta tag (método preferido)
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (metaToken) {
        return metaToken
    }

    // Método alternativo: intentar obtener de las cookies
    const cookies = document.cookie.split(';')
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=')
        if (name === 'XSRF-TOKEN' || name === 'csrf-token') {
            return decodeURIComponent(value)
        }
    }

    return null
}

// Función para recargar el token CSRF
export function refreshCsrfToken(): Promise<void> {
    return fetch('/csrf-token', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al recargar token CSRF')
        }
        return response.json()
    })
    .then(data => {
        // Actualizar el meta tag con el nuevo token
        const metaTag = document.querySelector('meta[name="csrf-token"]')
        if (metaTag && data.token) {
            metaTag.setAttribute('content', data.token)
        }
    })
    .catch(error => {
        console.error('Error recargando CSRF token:', error)
        // Si no se puede recargar, forzar recarga de página
        window.location.reload()
    })
}

// Función para hacer peticiones fetch con CSRF automáticamente incluido
export function fetchWithCsrf(url: string, options: RequestInit = {}): Promise<Response> {
    const csrfToken = getCsrfToken()

    if (!csrfToken) {
        console.error('No se encontró token CSRF')
        // Intentar recargar el token
        return refreshCsrfToken().then(() => {
            const retryToken = getCsrfToken()
            if (!retryToken) {
                throw new Error('No se pudo obtener token CSRF después de recargar')
            }

            const headers = {
                ...options.headers,
                'X-CSRF-TOKEN': retryToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }

            return fetch(url, { ...options, headers })
        })
    }

    const headers = {
        ...options.headers,
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }

    return fetch(url, { ...options, headers })
}