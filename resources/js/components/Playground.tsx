import React, { useState, useEffect } from "react"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Separator } from "@/components/ui/separator"
import { JsonView } from "react-json-view-lite"
import "react-json-view-lite/dist/index.css"
import {
  Search,
  Send,
  Plus,
  Trash2,
  Database,
  ChevronDown,
  ChevronRight,
  Loader2,
} from "lucide-react"

interface RouteParam {
  name: string
  type: string
  required: boolean
}

interface Route {
  method: string
  uri: string
  name: string
  model?: string
  params?: RouteParam[]
}

interface ModelField {
  name: string
  type: string
  hidden?: boolean
}

interface Model {
  name: string
  class: string
  routes: Route[]
  fields?: ModelField[]
}

interface Schema {
  routes: Route[]
  models: Model[]
}

interface KeyValuePair {
  key: string
  value: string
}

interface AuthConfig {
  type: "none" | "bearer" | "basic"
  token: string
  username: string
  password: string
}

interface RequestConfig {
  method: string
  url: string
  params: KeyValuePair[]
  headers: KeyValuePair[]
  auth: AuthConfig
  bodyType: "json" | "form"
  body: string
  formData: KeyValuePair[]
}

interface ResponseData {
  status: number
  statusText: string
  data: any
  headers: Record<string, string>
  time: number
}

export default function Playground() {
  const [schema, setSchema] = useState<Schema>({ routes: [], models: [] })
  const [searchQuery, setSearchQuery] = useState("")
  const [filteredRoutes, setFilteredRoutes] = useState<Route[]>([])
  const [selectedRoute, setSelectedRoute] = useState<Route | null>(null)
  const [selectedModel, setSelectedModel] = useState<Model | null>(null)
  const [loading, setLoading] = useState(false)

  const [request, setRequest] = useState<RequestConfig>({
    method: "GET",
    url: "",
    params: [],
    headers: [
      { key: "Accept", value: "application/json" },
      { key: "Content-Type", value: "application/json" },
    ],
    auth: { type: "none", token: "", username: "", password: "" },
    bodyType: "json",
    body: "",
    formData: [],
  })

  const [response, setResponse] = useState<ResponseData | null>(null)
  const [activeTab, setActiveTab] = useState("params")
  const [responseTab, setResponseTab] = useState("body")

  useEffect(() => {
    loadSchema()
  }, [])

  useEffect(() => {
    filterRoutes()
  }, [searchQuery, schema])

  const loadSchema = async () => {
    try {
      const res = await fetch("/volcanic/playground/schema")
      const data = await res.json()
      setSchema(data)
    } catch (error) {
      console.error("Failed to load schema:", error)
    }
  }

  const filterRoutes = () => {
    if (!searchQuery) {
      setFilteredRoutes(schema.routes || [])
      return
    }

    const query = searchQuery.toLowerCase()
    setFilteredRoutes(
      (schema.routes || []).filter(
        (route) =>
          route.uri.toLowerCase().includes(query) ||
          route.method.toLowerCase().includes(query) ||
          (route.name && route.name.toLowerCase().includes(query)),
      ),
    )
  }

  const selectRoute = (route) => {
    setSelectedRoute(route)
    setRequest((prev) => ({
      ...prev,
      url: route.uri,
      method: route.method,
    }))
  }

  const sendRequest = async () => {
    setLoading(true)
    const startTime = performance.now()

    try {
      let url = request.url
      const params = request.params.filter((p) => p.key && p.value)
      if (params.length > 0) {
        const queryString = params
          .map(
            (p) =>
              `${encodeURIComponent(p.key)}=${encodeURIComponent(p.value)}`,
          )
          .join("&")
        url += (url.includes("?") ? "&" : "?") + queryString
      }

      const headers = {}
      request.headers.forEach((h) => {
        if (h.key && h.value) headers[h.key] = h.value
      })

      if (request.auth.type === "bearer" && request.auth.token) {
        headers["Authorization"] = `Bearer ${request.auth.token}`
      } else if (request.auth.type === "basic" && request.auth.username) {
        const credentials = btoa(
          `${request.auth.username}:${request.auth.password}`,
        )
        headers["Authorization"] = `Basic ${credentials}`
      }

      let body = null
      if (["POST", "PUT", "PATCH"].includes(request.method)) {
        if (request.bodyType === "json") {
          body = request.body
        } else {
          const formData = {}
          request.formData.forEach((f) => {
            if (f.key && f.value) formData[f.key] = f.value
          })
          body = JSON.stringify(formData)
        }
      }

      const fetchOptions = { method: request.method, headers }
      if (body) fetchOptions.body = body

      const res = await fetch(url, fetchOptions)
      const endTime = performance.now()

      let data
      const contentType = res.headers.get("content-type")
      if (contentType && contentType.includes("application/json")) {
        data = await res.json()
      } else {
        data = await res.text()
      }

      const responseHeaders = {}
      res.headers.forEach((value, key) => {
        responseHeaders[key] = value
      })

      setResponse({
        status: res.status,
        statusText: res.statusText,
        time: Math.round(endTime - startTime),
        data: data,
        headers: responseHeaders,
      })
    } catch (error) {
      const endTime = performance.now()
      setResponse({
        status: 0,
        statusText: "Error",
        time: Math.round(endTime - startTime),
        data: { error: error.message },
        headers: {},
      })
    } finally {
      setLoading(false)
    }
  }

  const getMethodColor = (method) => {
    const colors = {
      GET: "bg-green-100 text-green-700",
      POST: "bg-blue-100 text-blue-700",
      PUT: "bg-yellow-100 text-yellow-700",
      PATCH: "bg-yellow-100 text-yellow-700",
      DELETE: "bg-red-100 text-red-700",
    }
    return colors[method] || "bg-gray-100 text-gray-700"
  }

  const getStatusColor = (status) => {
    if (status >= 200 && status < 300) return "bg-green-100 text-green-700"
    if (status >= 300 && status < 400) return "bg-yellow-100 text-yellow-700"
    return "bg-red-100 text-red-700"
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-gradient-to-r from-orange-600 to-red-600 text-white shadow-lg">
        <div className="container mx-auto px-4 py-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <i className="fas fa-volcano text-3xl"></i>
              <div>
                <h1 className="text-2xl font-bold">Volcanic API Playground</h1>
                <p className="text-orange-100 text-sm">
                  Interactive REST API Explorer
                </p>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm bg-white/20 px-3 py-1 rounded-full">
                {schema.routes?.length || 0} Routes
              </span>
              <span className="text-sm bg-white/20 px-3 py-1 rounded-full">
                {schema.models?.length || 0} Models
              </span>
            </div>
          </div>
        </div>
      </header>

      <div className="container mx-auto px-4 py-6">
        <div className="grid grid-cols-12 gap-6">
          {/* Sidebar */}
          <div className="col-span-3 space-y-4">
            {/* Search */}
            <div className="bg-white rounded-lg shadow-md p-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                <Input
                  type="text"
                  placeholder="Search routes..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            {/* Routes List */}
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
              <div className="bg-gray-100 px-4 py-3 border-b">
                <h2 className="font-semibold text-gray-700">API Routes</h2>
              </div>
              <ScrollArea className="h-96">
                {filteredRoutes.map((route, idx) => (
                  <div
                    key={idx}
                    onClick={() => selectRoute(route)}
                    className={`px-4 py-3 border-b cursor-pointer transition-colors ${
                      selectedRoute?.uri === route.uri &&
                      selectedRoute?.method === route.method
                        ? "bg-orange-50 border-l-4 border-orange-500"
                        : "hover:bg-gray-50"
                    }`}
                  >
                    <div className="flex items-center space-x-2">
                      <span
                        className={`text-xs font-semibold px-2 py-1 rounded ${getMethodColor(
                          route.method,
                        )}`}
                      >
                        {route.method}
                      </span>
                      <span className="text-sm text-gray-700 truncate">
                        {route.uri}
                      </span>
                    </div>
                  </div>
                ))}
                {filteredRoutes.length === 0 && (
                  <div className="px-4 py-8 text-center text-gray-500">
                    <Search className="mx-auto h-8 w-8 mb-2" />
                    <p>No routes found</p>
                  </div>
                )}
              </ScrollArea>
            </div>

            {/* Models List */}
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
              <div className="bg-gray-100 px-4 py-3 border-b">
                <h2 className="font-semibold text-gray-700">Models</h2>
              </div>
              <ScrollArea className="h-64">
                {schema.models?.map((model, idx) => (
                  <div key={idx} className="border-b">
                    <div
                      onClick={() =>
                        setSelectedModel(
                          selectedModel?.class === model.class ? null : model,
                        )
                      }
                      className="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                          <Database className="h-4 w-4 text-gray-400" />
                          <span className="text-sm font-medium">
                            {model.name}
                          </span>
                        </div>
                        {selectedModel?.class === model.class ? (
                          <ChevronDown className="h-4 w-4 text-gray-400" />
                        ) : (
                          <ChevronRight className="h-4 w-4 text-gray-400" />
                        )}
                      </div>
                    </div>
                    {selectedModel?.class === model.class && (
                      <div className="px-4 pb-3 space-y-1">
                        <div className="text-xs text-gray-500 mb-2">
                          Fields:
                        </div>
                        {model.fields?.map((field, fieldIdx) => (
                          <div
                            key={fieldIdx}
                            className="flex justify-between text-xs py-1 px-2 bg-gray-50 rounded"
                          >
                            <span className="font-mono text-gray-700">
                              {field.name}
                            </span>
                            <span className="text-gray-500">{field.type}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </ScrollArea>
            </div>
          </div>

          {/* Main Content */}
          <div className="col-span-9 space-y-4">
            {/* Request Builder */}
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="space-y-4">
                {/* URL Bar */}
                <div className="flex space-x-2">
                  <Select
                    value={request.method}
                    onValueChange={(value) =>
                      setRequest({
                        ...request,
                        method: value,
                      })
                    }
                  >
                    <SelectTrigger className="w-32">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="GET">GET</SelectItem>
                      <SelectItem value="POST">POST</SelectItem>
                      <SelectItem value="PUT">PUT</SelectItem>
                      <SelectItem value="PATCH">PATCH</SelectItem>
                      <SelectItem value="DELETE">DELETE</SelectItem>
                    </SelectContent>
                  </Select>
                  <Input
                    type="text"
                    placeholder="Enter request URL (e.g., /api/products)"
                    value={request.url}
                    onChange={(e) =>
                      setRequest({
                        ...request,
                        url: e.target.value,
                      })
                    }
                    className="flex-1"
                  />
                  <Button
                    onClick={sendRequest}
                    disabled={loading}
                    className="bg-orange-600 hover:bg-orange-700"
                  >
                    {loading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />{" "}
                        Sending...
                      </>
                    ) : (
                      <>
                        <Send className="mr-2 h-4 w-4" /> Send
                      </>
                    )}
                  </Button>
                </div>

                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                  <TabsList>
                    <TabsTrigger value="params">Query Params</TabsTrigger>
                    <TabsTrigger value="headers">Headers</TabsTrigger>
                    <TabsTrigger value="auth">Authorization</TabsTrigger>
                    {["POST", "PUT", "PATCH"].includes(request.method) && (
                      <TabsTrigger value="body">Body</TabsTrigger>
                    )}
                  </TabsList>

                  <TabsContent value="params" className="space-y-2">
                    {request.params.map((param, idx) => (
                      <div key={idx} className="flex space-x-2">
                        <Input
                          placeholder="Key"
                          value={param.key}
                          onChange={(e) => {
                            const newParams = [...request.params]
                            newParams[idx].key = e.target.value
                            setRequest({
                              ...request,
                              params: newParams,
                            })
                          }}
                        />
                        <Input
                          placeholder="Value"
                          value={param.value}
                          onChange={(e) => {
                            const newParams = [...request.params]
                            newParams[idx].value = e.target.value
                            setRequest({
                              ...request,
                              params: newParams,
                            })
                          }}
                        />
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            const newParams = request.params.filter(
                              (_, i) => i !== idx,
                            )
                            setRequest({
                              ...request,
                              params: newParams,
                            })
                          }}
                        >
                          <Trash2 className="h-4 w-4 text-red-600" />
                        </Button>
                      </div>
                    ))}
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() =>
                        setRequest({
                          ...request,
                          params: [...request.params, { key: "", value: "" }],
                        })
                      }
                      className="text-orange-600 hover:text-orange-700"
                    >
                      <Plus className="h-4 w-4 mr-2" /> Add Parameter
                    </Button>
                  </TabsContent>

                  <TabsContent value="headers" className="space-y-2">
                    {request.headers.map((header, idx) => (
                      <div key={idx} className="flex space-x-2">
                        <Input
                          placeholder="Key"
                          value={header.key}
                          onChange={(e) => {
                            const newHeaders = [...request.headers]
                            newHeaders[idx].key = e.target.value
                            setRequest({
                              ...request,
                              headers: newHeaders,
                            })
                          }}
                        />
                        <Input
                          placeholder="Value"
                          value={header.value}
                          onChange={(e) => {
                            const newHeaders = [...request.headers]
                            newHeaders[idx].value = e.target.value
                            setRequest({
                              ...request,
                              headers: newHeaders,
                            })
                          }}
                        />
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            const newHeaders = request.headers.filter(
                              (_, i) => i !== idx,
                            )
                            setRequest({
                              ...request,
                              headers: newHeaders,
                            })
                          }}
                        >
                          <Trash2 className="h-4 w-4 text-red-600" />
                        </Button>
                      </div>
                    ))}
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() =>
                        setRequest({
                          ...request,
                          headers: [...request.headers, { key: "", value: "" }],
                        })
                      }
                      className="text-orange-600 hover:text-orange-700"
                    >
                      <Plus className="h-4 w-4 mr-2" /> Add Header
                    </Button>
                  </TabsContent>

                  <TabsContent value="auth" className="space-y-4">
                    <div>
                      <Label>Auth Type</Label>
                      <Select
                        value={request.auth.type}
                        onValueChange={(value) =>
                          setRequest({
                            ...request,
                            auth: {
                              ...request.auth,
                              type: value,
                            },
                          })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="none">No Auth</SelectItem>
                          <SelectItem value="bearer">Bearer Token</SelectItem>
                          <SelectItem value="basic">Basic Auth</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    {request.auth.type === "bearer" && (
                      <div>
                        <Label>Token</Label>
                        <Input
                          placeholder="Enter your bearer token"
                          value={request.auth.token}
                          onChange={(e) =>
                            setRequest({
                              ...request,
                              auth: {
                                ...request.auth,
                                token: e.target.value,
                              },
                            })
                          }
                        />
                      </div>
                    )}
                    {request.auth.type === "basic" && (
                      <div className="space-y-2">
                        <div>
                          <Label>Username</Label>
                          <Input
                            placeholder="Username"
                            value={request.auth.username}
                            onChange={(e) =>
                              setRequest({
                                ...request,
                                auth: {
                                  ...request.auth,
                                  username: e.target.value,
                                },
                              })
                            }
                          />
                        </div>
                        <div>
                          <Label>Password</Label>
                          <Input
                            type="password"
                            placeholder="Password"
                            value={request.auth.password}
                            onChange={(e) =>
                              setRequest({
                                ...request,
                                auth: {
                                  ...request.auth,
                                  password: e.target.value,
                                },
                              })
                            }
                          />
                        </div>
                      </div>
                    )}
                  </TabsContent>

                  <TabsContent value="body" className="space-y-4">
                    <div className="flex space-x-4">
                      <label className="flex items-center space-x-2">
                        <input
                          type="radio"
                          checked={request.bodyType === "json"}
                          onChange={() =>
                            setRequest({
                              ...request,
                              bodyType: "json",
                            })
                          }
                          className="text-orange-600"
                        />
                        <span className="text-sm">JSON</span>
                      </label>
                      <label className="flex items-center space-x-2">
                        <input
                          type="radio"
                          checked={request.bodyType === "form"}
                          onChange={() =>
                            setRequest({
                              ...request,
                              bodyType: "form",
                            })
                          }
                          className="text-orange-600"
                        />
                        <span className="text-sm">Form Data</span>
                      </label>
                    </div>
                    {request.bodyType === "json" ? (
                      <Textarea
                        placeholder='{\n  "key": "value"\n}'
                        value={request.body}
                        onChange={(e) =>
                          setRequest({
                            ...request,
                            body: e.target.value,
                          })
                        }
                        className="h-48 font-mono text-sm"
                      />
                    ) : (
                      <div className="space-y-2">
                        {request.formData.map((field, idx) => (
                          <div key={idx} className="flex space-x-2">
                            <Input
                              placeholder="Key"
                              value={field.key}
                              onChange={(e) => {
                                const newFormData = [...request.formData]
                                newFormData[idx].key = e.target.value
                                setRequest({
                                  ...request,
                                  formData: newFormData,
                                })
                              }}
                            />
                            <Input
                              placeholder="Value"
                              value={field.value}
                              onChange={(e) => {
                                const newFormData = [...request.formData]
                                newFormData[idx].value = e.target.value
                                setRequest({
                                  ...request,
                                  formData: newFormData,
                                })
                              }}
                            />
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => {
                                const newFormData = request.formData.filter(
                                  (_, i) => i !== idx,
                                )
                                setRequest({
                                  ...request,
                                  formData: newFormData,
                                })
                              }}
                            >
                              <Trash2 className="h-4 w-4 text-red-600" />
                            </Button>
                          </div>
                        ))}
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() =>
                            setRequest({
                              ...request,
                              formData: [
                                ...request.formData,
                                {
                                  key: "",
                                  value: "",
                                },
                              ],
                            })
                          }
                          className="text-orange-600 hover:text-orange-700"
                        >
                          <Plus className="h-4 w-4 mr-2" /> Add Field
                        </Button>
                      </div>
                    )}
                  </TabsContent>
                </Tabs>
              </div>
            </div>

            {/* Response */}
            {response && (
              <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="bg-gray-100 px-6 py-3 border-b flex items-center justify-between">
                  <h2 className="font-semibold text-gray-700">Response</h2>
                  <div className="flex items-center space-x-3">
                    <span
                      className={`text-sm font-semibold px-3 py-1 rounded ${getStatusColor(
                        response.status,
                      )}`}
                    >
                      {response.status} {response.statusText}
                    </span>
                    <span className="text-sm text-gray-500">
                      {response.time}ms
                    </span>
                  </div>
                </div>
                <div className="p-6">
                  <Tabs value={responseTab} onValueChange={setResponseTab}>
                    <TabsList>
                      <TabsTrigger value="body">Body</TabsTrigger>
                      <TabsTrigger value="headers">Headers</TabsTrigger>
                    </TabsList>
                    <TabsContent value="body">
                      <div className="bg-gray-50 p-4 rounded-md overflow-x-auto">
                        {typeof response.data === "object" ? (
                          <JsonView
                            data={response.data}
                            shouldInitiallyExpand={(level) => level < 2}
                          />
                        ) : (
                          <pre className="text-sm font-mono">
                            {String(response.data)}
                          </pre>
                        )}
                      </div>
                    </TabsContent>
                    <TabsContent value="headers">
                      <div className="space-y-2">
                        {Object.entries(response.headers).map(
                          ([key, value]) => (
                            <div
                              key={key}
                              className="flex items-start space-x-2 text-sm"
                            >
                              <span className="font-semibold text-gray-700 min-w-[200px]">
                                {key}:
                              </span>
                              <span className="text-gray-600">{value}</span>
                            </div>
                          ),
                        )}
                      </div>
                    </TabsContent>
                  </Tabs>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
