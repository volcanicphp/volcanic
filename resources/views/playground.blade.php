<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Volcanic API Playground</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            [x-cloak] {
                display: none !important;
            }

            .monaco-editor-container {
                border: 1px solid #e5e7eb;
                border-radius: 0.375rem;
            }

            .autocomplete-dropdown {
                max-height: 300px;
                overflow-y: auto;
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <div x-data="playground()" x-init="init()" class="min-h-screen">
            <!-- Header -->
            <header class="bg-gradient-to-r from-orange-600 to-red-600 text-white shadow-lg">
                <div class="container mx-auto px-4 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-volcano text-3xl"></i>
                            <div>
                                <h1 class="text-2xl font-bold">Volcanic API Playground</h1>
                                <p class="text-orange-100 text-sm">Interactive REST API Explorer</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm bg-white/20 px-3 py-1 rounded-full"
                                x-text="`${schema.routes?.length || 0} Routes`"></span>
                            <span class="text-sm bg-white/20 px-3 py-1 rounded-full"
                                x-text="`${schema.models?.length || 0} Models`"></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container mx-auto px-4 py-6">
                <div class="grid grid-cols-12 gap-6">
                    <!-- Sidebar - Routes & Models -->
                    <div class="col-span-3 space-y-4">
                        <!-- Search -->
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <input type="text" x-model="searchQuery" @input="filterRoutes()"
                                placeholder="Search routes..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>

                        <!-- Routes List -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
                                <h2 class="font-semibold text-gray-700">API Routes</h2>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <template x-for="route in filteredRoutes" :key="route.uri + route.method">
                                    <div @click="selectRoute(route)"
                                        :class="selectedRoute?.uri === route.uri && selectedRoute?.method === route.method ? 'bg-orange-50 border-l-4 border-orange-500' : 'hover:bg-gray-50'"
                                        class="px-4 py-3 border-b border-gray-100 cursor-pointer transition-colors">
                                        <div class="flex items-center space-x-2">
                                            <span :class="{
                                                'bg-green-100 text-green-700': route.method === 'GET',
                                                'bg-blue-100 text-blue-700': route.method === 'POST',
                                                'bg-yellow-100 text-yellow-700': route.method === 'PUT' || route.method === 'PATCH',
                                                'bg-red-100 text-red-700': route.method === 'DELETE'
                                            }" class="text-xs font-semibold px-2 py-1 rounded"
                                                x-text="route.method"></span>
                                            <span class="text-sm text-gray-700 truncate" x-text="route.uri"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="filteredRoutes.length === 0">
                                    <div class="px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-search text-2xl mb-2"></i>
                                        <p>No routes found</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Models List -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gray-100 px-4 py-3 border-b border-gray-200">
                                <h2 class="font-semibold text-gray-700">Models</h2>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <template x-for="model in schema.models" :key="model.class">
                                    <div @click="selectedModel = selectedModel?.class === model.class ? null : model"
                                        class="px-4 py-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-database text-gray-400"></i>
                                                <span class="text-sm font-medium text-gray-700"
                                                    x-text="model.name"></span>
                                            </div>
                                            <i class="fas fa-chevron-down text-xs text-gray-400"
                                                :class="selectedModel?.class === model.class && 'transform rotate-180'"></i>
                                        </div>
                                        <div x-show="selectedModel?.class === model.class" x-cloak
                                            class="mt-3 space-y-1">
                                            <div class="text-xs text-gray-500 mb-2">Fields:</div>
                                            <template x-for="field in model.fields" :key="field.name">
                                                <div
                                                    class="flex items-center justify-between text-xs py-1 px-2 bg-gray-50 rounded">
                                                    <span class="font-mono text-gray-700" x-text="field.name"></span>
                                                    <span class="text-gray-500" x-text="field.type"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content - Request Builder -->
                    <div class="col-span-9 space-y-4">
                        <!-- Request Configuration -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="space-y-4">
                                <!-- URL Bar -->
                                <div class="flex space-x-2">
                                    <select x-model="request.method"
                                        class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                    <div class="flex-1 relative">
                                        <input type="text" x-model="request.url"
                                            @input="showAutocomplete = true; filterAutocomplete()"
                                            @focus="showAutocomplete = true; filterAutocomplete()"
                                            @blur="setTimeout(() => showAutocomplete = false, 200)"
                                            placeholder="Enter request URL (e.g., /api/products)"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        <!-- Autocomplete Dropdown -->
                                        <div x-show="showAutocomplete && autocompleteResults.length > 0" x-cloak
                                            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg autocomplete-dropdown">
                                            <template x-for="result in autocompleteResults" :key="result.uri">
                                                <div @click="selectAutocomplete(result)"
                                                    class="px-4 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                                    <div class="flex items-center space-x-2">
                                                        <span :class="{
                                                            'bg-green-100 text-green-700': result.method === 'GET',
                                                            'bg-blue-100 text-blue-700': result.method === 'POST',
                                                            'bg-yellow-100 text-yellow-700': result.method === 'PUT' || result.method === 'PATCH',
                                                            'bg-red-100 text-red-700': result.method === 'DELETE'
                                                        }" class="text-xs font-semibold px-2 py-1 rounded"
                                                            x-text="result.method"></span>
                                                        <span class="text-sm text-gray-700" x-text="result.uri"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <button @click="sendRequest()" :disabled="loading"
                                        class="px-6 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <span x-show="!loading">Send</span>
                                        <span x-show="loading">
                                            <i class="fas fa-spinner fa-spin"></i> Sending...
                                        </span>
                                    </button>
                                </div>

                                <!-- Tabs -->
                                <div class="border-b border-gray-200">
                                    <nav class="flex space-x-4">
                                        <button @click="activeTab = 'params'"
                                            :class="activeTab === 'params' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                                            Query Params
                                        </button>
                                        <button @click="activeTab = 'headers'"
                                            :class="activeTab === 'headers' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                                            Headers
                                        </button>
                                        <button @click="activeTab = 'auth'"
                                            :class="activeTab === 'auth' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                                            Authorization
                                        </button>
                                        <button @click="activeTab = 'body'"
                                            :class="activeTab === 'body' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm transition-colors"
                                            x-show="['POST', 'PUT', 'PATCH'].includes(request.method)">
                                            Body
                                        </button>
                                    </nav>
                                </div>

                                <!-- Tab Content -->
                                <div class="mt-4">
                                    <!-- Query Params -->
                                    <div x-show="activeTab === 'params'" x-cloak>
                                        <div class="space-y-2">
                                            <template x-for="(param, index) in request.params" :key="index">
                                                <div class="flex space-x-2">
                                                    <input type="text" x-model="param.key" placeholder="Key"
                                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                    <input type="text" x-model="param.value" placeholder="Value"
                                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                    <button @click="request.params.splice(index, 1)"
                                                        class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <button @click="request.params.push({ key: '', value: '' })"
                                                class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                                                <i class="fas fa-plus"></i> Add Parameter
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Headers -->
                                    <div x-show="activeTab === 'headers'" x-cloak>
                                        <div class="space-y-2">
                                            <template x-for="(header, index) in request.headers" :key="index">
                                                <div class="flex space-x-2">
                                                    <input type="text" x-model="header.key" placeholder="Key"
                                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                    <input type="text" x-model="header.value" placeholder="Value"
                                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                    <button @click="request.headers.splice(index, 1)"
                                                        class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <button @click="request.headers.push({ key: '', value: '' })"
                                                class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                                                <i class="fas fa-plus"></i> Add Header
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Authorization -->
                                    <div x-show="activeTab === 'auth'" x-cloak>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Auth
                                                    Type</label>
                                                <select x-model="request.auth.type"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                    <option value="none">No Auth</option>
                                                    <option value="bearer">Bearer Token</option>
                                                    <option value="basic">Basic Auth</option>
                                                </select>
                                            </div>
                                            <div x-show="request.auth.type === 'bearer'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 mb-2">Token</label>
                                                <input type="text" x-model="request.auth.token"
                                                    placeholder="Enter your bearer token"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            </div>
                                            <div x-show="request.auth.type === 'basic'" class="space-y-2">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                                    <input type="text" x-model="request.auth.username"
                                                        placeholder="Username"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                                    <input type="password" x-model="request.auth.password"
                                                        placeholder="Password"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div x-show="activeTab === 'body'" x-cloak>
                                        <div class="space-y-4">
                                            <div class="flex space-x-4">
                                                <label class="flex items-center space-x-2">
                                                    <input type="radio" x-model="request.bodyType" value="json"
                                                        class="text-orange-600 focus:ring-orange-500">
                                                    <span class="text-sm">JSON</span>
                                                </label>
                                                <label class="flex items-center space-x-2">
                                                    <input type="radio" x-model="request.bodyType" value="form"
                                                        class="text-orange-600 focus:ring-orange-500">
                                                    <span class="text-sm">Form Data</span>
                                                </label>
                                            </div>
                                            <div x-show="request.bodyType === 'json'">
                                                <textarea x-model="request.body" placeholder='{\n  "key": "value"\n}'
                                                    class="w-full h-48 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono text-sm"></textarea>
                                            </div>
                                            <div x-show="request.bodyType === 'form'" class="space-y-2">
                                                <template x-for="(field, index) in request.formData" :key="index">
                                                    <div class="flex space-x-2">
                                                        <input type="text" x-model="field.key" placeholder="Key"
                                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                        <input type="text" x-model="field.value" placeholder="Value"
                                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                        <button @click="request.formData.splice(index, 1)"
                                                            class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <button @click="request.formData.push({ key: '', value: '' })"
                                                    class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                                                    <i class="fas fa-plus"></i> Add Field
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden" x-show="response !== null" x-cloak>
                            <div
                                class="bg-gray-100 px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                                <h2 class="font-semibold text-gray-700">Response</h2>
                                <div class="flex items-center space-x-3" x-show="response">
                                    <span :class="{
                                        'bg-green-100 text-green-700': response?.status >= 200 && response?.status < 300,
                                        'bg-yellow-100 text-yellow-700': response?.status >= 300 && response?.status < 400,
                                        'bg-red-100 text-red-700': response?.status >= 400
                                    }" class="text-sm font-semibold px-3 py-1 rounded"
                                        x-text="`${response?.status} ${response?.statusText}`"></span>
                                    <span class="text-sm text-gray-500" x-text="`${response?.time}ms`"></span>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <div class="flex space-x-4 border-b border-gray-200">
                                        <button @click="responseTab = 'body'"
                                            :class="responseTab === 'body' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm">
                                            Body
                                        </button>
                                        <button @click="responseTab = 'headers'"
                                            :class="responseTab === 'headers' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500'"
                                            class="py-2 px-1 border-b-2 font-medium text-sm">
                                            Headers
                                        </button>
                                    </div>
                                </div>
                                <div x-show="responseTab === 'body'">
                                    <pre class="bg-gray-50 p-4 rounded-md overflow-x-auto text-sm font-mono"
                                        x-text="formatJSON(response?.data)"></pre>
                                </div>
                                <div x-show="responseTab === 'headers'" x-cloak>
                                    <div class="space-y-2">
                                        <template x-for="(value, key) in response?.headers" :key="key">
                                            <div class="flex items-start space-x-2 text-sm">
                                                <span class="font-semibold text-gray-700 min-w-[200px]"
                                                    x-text="key + ':'"></span>
                                                <span class="text-gray-600" x-text="value"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function playground() {
                return {
                    schema: {
                        routes: [],
                        models: []
                    },
                    searchQuery: '',
                    filteredRoutes: [],
                    autocompleteResults: [],
                    showAutocomplete: false,
                    selectedRoute: null,
                    selectedModel: null,
                    activeTab: 'params',
                    responseTab: 'body',
                    loading: false,
                    request: {
                        method: 'GET',
                        url: '',
                        params: [],
                        headers: [
                            { key: 'Accept', value: 'application/json' },
                            { key: 'Content-Type', value: 'application/json' }
                        ],
                        auth: {
                            type: 'none',
                            token: '',
                            username: '',
                            password: ''
                        },
                        bodyType: 'json',
                        body: '',
                        formData: []
                    },
                    response: null,

                    async init() {
                        await this.loadSchema();
                        this.filterRoutes();
                    },

                    async loadSchema() {
                        try {
                            const response = await fetch('/volcanic/playground/schema');
                            this.schema = await response.json();
                            this.filterRoutes();
                        } catch (error) {
                            console.error('Failed to load schema:', error);
                        }
                    },

                    filterRoutes() {
                        if (!this.searchQuery) {
                            this.filteredRoutes = this.schema.routes || [];
                            return;
                        }

                        const query = this.searchQuery.toLowerCase();
                        this.filteredRoutes = (this.schema.routes || []).filter(route =>
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
                        this.autocompleteResults = (this.schema.routes || []).filter(route =>
                            route.uri.toLowerCase().includes(query)
                        ).slice(0, 10);
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
                            const params = this.request.params.filter(p => p.key && p.value);
                            if (params.length > 0) {
                                const queryString = params.map(p =>
                                    `${encodeURIComponent(p.key)}=${encodeURIComponent(p.value)}`
                                ).join('&');
                                url += (url.includes('?') ? '&' : '?') + queryString;
                            }

                            // Build headers
                            const headers = {};
                            this.request.headers.forEach(h => {
                                if (h.key && h.value) {
                                    headers[h.key] = h.value;
                                }
                            });

                            // Add authorization
                            if (this.request.auth.type === 'bearer' && this.request.auth.token) {
                                headers['Authorization'] = `Bearer ${this.request.auth.token}`;
                            } else if (this.request.auth.type === 'basic' && this.request.auth.username) {
                                const credentials = btoa(`${this.request.auth.username}:${this.request.auth.password}`);
                                headers['Authorization'] = `Basic ${credentials}`;
                            }

                            // Build body
                            let body = null;
                            if (['POST', 'PUT', 'PATCH'].includes(this.request.method)) {
                                if (this.request.bodyType === 'json') {
                                    body = this.request.body;
                                } else {
                                    const formData = {};
                                    this.request.formData.forEach(f => {
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
                                headers: headers
                            };

                            if (body) {
                                fetchOptions.body = body;
                            }

                            const response = await fetch(url, fetchOptions);
                            const endTime = performance.now();

                            // Parse response
                            let data;
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
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
                                headers: responseHeaders
                            };
                        } catch (error) {
                            const endTime = performance.now();
                            this.response = {
                                status: 0,
                                statusText: 'Error',
                                time: Math.round(endTime - startTime),
                                data: { error: error.message },
                                headers: {}
                            };
                        } finally {
                            this.loading = false;
                        }
                    },

                    formatJSON(data) {
                        if (typeof data === 'string') {
                            try {
                                data = JSON.parse(data);
                            } catch (e) {
                                return data;
                            }
                        }
                        return JSON.stringify(data, null, 2);
                    }
                };
            }
        </script>
    </body>
</html>
