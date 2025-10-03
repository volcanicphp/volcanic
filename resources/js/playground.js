import Alpine from "alpinejs";

// Define playground component BEFORE starting Alpine
window.playground = function () {
    return {
        schema: {
            routes: [],
            models: [],
        },
        searchQuery: "",
        filteredRoutes: [],
        autocompleteResults: [],
        showAutocomplete: false,
        selectedRoute: null,
        selectedModel: null,
        activeTab: "params",
        responseTab: "body",
        loading: false,
        request: {
            method: "GET",
            url: "",
            params: [],
            headers: [
                { key: "Accept", value: "application/json" },
                { key: "Content-Type", value: "application/json" },
            ],
            auth: {
                type: "none",
                token: "",
                username: "",
                password: "",
            },
            bodyType: "json",
            body: "",
            formData: [],
        },
        response: null,

        async init() {
            await this.loadSchema();
            this.filterRoutes();
        },

        async loadSchema() {
            try {
                const response = await fetch("/volcanic/playground/schema");
                this.schema = await response.json();
                this.filterRoutes();
            } catch (error) {
                console.error("Failed to load schema:", error);
            }
        },

        filterRoutes() {
            if (!this.searchQuery) {
                this.filteredRoutes = this.schema.routes || [];
                return;
            }

            const query = this.searchQuery.toLowerCase();
            this.filteredRoutes = (this.schema.routes || []).filter(
                (route) =>
                    route.uri.toLowerCase().includes(query) ||
                    route.method.toLowerCase().includes(query) ||
                    (route.name && route.name.toLowerCase().includes(query))
            );
        },

        filterAutocomplete() {
            if (!this.request.url) {
                this.autocompleteResults = this.schema.routes || [];
                return;
            }

            const query = this.request.url.toLowerCase();
            this.autocompleteResults = (this.schema.routes || [])
                .filter((route) => route.uri.toLowerCase().includes(query))
                .slice(0, 10);
        },

        selectAutocomplete(route) {
            this.request.url = route.uri;
            this.request.method = route.method;
            this.showAutocomplete = false;
            this.selectedRoute = route;
        },

        selectRoute(route) {
            this.selectedRoute = route;
            this.request.url = route.uri;
            this.request.method = route.method;
        },

        async sendRequest() {
            this.loading = true;
            const startTime = performance.now();

            try {
                // Build URL with query params
                let url = this.request.url;
                const params = this.request.params.filter(
                    (p) => p.key && p.value
                );
                if (params.length > 0) {
                    const queryString = params
                        .map(
                            (p) =>
                                `${encodeURIComponent(
                                    p.key
                                )}=${encodeURIComponent(p.value)}`
                        )
                        .join("&");
                    url += (url.includes("?") ? "&" : "?") + queryString;
                }

                // Build headers
                const headers = {};
                this.request.headers.forEach((h) => {
                    if (h.key && h.value) {
                        headers[h.key] = h.value;
                    }
                });

                // Add authorization
                if (
                    this.request.auth.type === "bearer" &&
                    this.request.auth.token
                ) {
                    headers[
                        "Authorization"
                    ] = `Bearer ${this.request.auth.token}`;
                } else if (
                    this.request.auth.type === "basic" &&
                    this.request.auth.username
                ) {
                    const credentials = btoa(
                        `${this.request.auth.username}:${this.request.auth.password}`
                    );
                    headers["Authorization"] = `Basic ${credentials}`;
                }

                // Build body
                let body = null;
                if (["POST", "PUT", "PATCH"].includes(this.request.method)) {
                    if (this.request.bodyType === "json") {
                        body = this.request.body;
                    } else {
                        const formData = {};
                        this.request.formData.forEach((f) => {
                            if (f.key && f.value) {
                                formData[f.key] = f.value;
                            }
                        });
                        body = JSON.stringify(formData);
                    }
                }

                // Make request
                const fetchOptions = {
                    method: this.request.method,
                    headers: headers,
                };

                if (body) {
                    fetchOptions.body = body;
                }

                const response = await fetch(url, fetchOptions);
                const endTime = performance.now();

                // Parse response
                let data;
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }

                // Extract headers
                const responseHeaders = {};
                response.headers.forEach((value, key) => {
                    responseHeaders[key] = value;
                });

                this.response = {
                    status: response.status,
                    statusText: response.statusText,
                    time: Math.round(endTime - startTime),
                    data: data,
                    headers: responseHeaders,
                };
            } catch (error) {
                const endTime = performance.now();
                this.response = {
                    status: 0,
                    statusText: "Error",
                    time: Math.round(endTime - startTime),
                    data: { error: error.message },
                    headers: {},
                };
            } finally {
                this.loading = false;
            }
        },

        formatJSON(data) {
            if (typeof data === "string") {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    return data;
                }
            }
            return JSON.stringify(data, null, 2);
        },
    };
};

// Start Alpine AFTER defining the playground component
window.Alpine = Alpine;
Alpine.start();
