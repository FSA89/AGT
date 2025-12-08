// api/apiClient.js
export async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            "Content-Type": "application/json"
        },
        ...options
    });

    if (!response.ok) {
        throw new Error(`Ошибка API: ${response.status}`);
    }

    return response.json();
}
