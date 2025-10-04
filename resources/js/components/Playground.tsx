import { useState, useEffect, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarInset,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import { Separator } from "@/components/ui/separator"
import { ThemeToggle } from "@/components/theme-toggle"
import { Light as SyntaxHighlighter } from "react-syntax-highlighter"
import { vscDarkPlus, vs } from "react-syntax-highlighter/dist/esm/styles/prism"
import html from "react-syntax-highlighter/dist/esm/languages/prism/markup"
import json from "react-syntax-highlighter/dist/esm/languages/prism/json"
import {
  Search,
  Send,
  Plus,
  Trash2,
  Monitor,
  Tablet,
  Smartphone,
} from "lucide-react"

// Register languages for syntax highlighting
SyntaxHighlighter.registerLanguage("html", html)
SyntaxHighlighter.registerLanguage("json", json)

// Types
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
  prefix?: string
}

interface Schema {
  routes: Route[]
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
  contentType: string
  isHtml: boolean
  isJson: boolean
  isText: boolean
}

type DeviceSize = "mobile" | "tablet" | "desktop"

// Common HTTP headers and their suggested values
const HEADER_SUGGESTIONS: Record<string, string[]> = {
  Accept: [
    "application/json",
    "application/xml",
    "text/html",
    "text/plain",
    "application/pdf",
    "*/*",
  ],
  "Content-Type": [
    "application/json",
    "application/x-www-form-urlencoded",
    "multipart/form-data",
    "application/xml",
    "text/html",
    "text/plain",
  ],
  Authorization: ["Bearer ", "Basic "],
  "Cache-Control": [
    "no-cache",
    "no-store",
    "max-age=3600",
    "must-revalidate",
    "public",
    "private",
  ],
  "User-Agent": [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36",
    "PostmanRuntime/7.32.0",
  ],
  "Accept-Encoding": ["gzip, deflate, br", "gzip", "deflate"],
  "Accept-Language": ["en-US,en;q=0.9", "en-GB,en;q=0.9", "pt-BR,pt;q=0.9"],
  Connection: ["keep-alive", "close"],
  Origin: ["http://localhost", "https://example.com"],
  Referer: ["http://localhost", "https://example.com"],
  "X-Requested-With": ["XMLHttpRequest"],
  "X-CSRF-Token": [""],
  "X-API-Key": [""],
  "If-Modified-Since": [""],
  "If-None-Match": [""],
}

const COMMON_HEADERS = Object.keys(HEADER_SUGGESTIONS)

export default function Playground() {
  const [schema, setSchema] = useState<Schema>({ routes: [] })
  const [selectedRoute, setSelectedRoute] = useState<Route | null>(null)
  const [searchQuery, setSearchQuery] = useState("")
  const [request, setRequest] = useState<RequestConfig>({
    method: "GET",
    url: "",
    params: [],
    headers: [],
    auth: {
      type: "none",
      token: "",
      username: "",
      password: "",
    },
    bodyType: "json",
    body: "",
    formData: [],
  })
  const [response, setResponse] = useState<ResponseData | null>(null)
  const [loading, setLoading] = useState(false)
  const [responseTab, setResponseTab] = useState("body")
  const [deviceSize, setDeviceSize] = useState<DeviceSize>("desktop")
  const iframeRef = useRef<HTMLIFrameElement>(null)
  const [isDarkMode, setIsDarkMode] = useState(false)

  // Detect theme changes
  useEffect(() => {
    const checkTheme = () => {
      setIsDarkMode(document.documentElement.classList.contains("dark"))
    }

    checkTheme()

    const observer = new MutationObserver(checkTheme)
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["class"],
    })

    return () => observer.disconnect()
  }, [])

  useEffect(() => {
    fetch("/__schema__")
      .then((res) => res.json())
      .then((data) => setSchema(data))
      .catch((err) => console.error("Failed to load schema:", err))
  }, [])

  const filteredRoutes = schema.routes.filter(
    (route) =>
      route.uri.toLowerCase().includes(searchQuery.toLowerCase()) ||
      route.method.toLowerCase().includes(searchQuery.toLowerCase()) ||
      route.name?.toLowerCase().includes(searchQuery.toLowerCase()),
  )

  const groupedRoutes = filteredRoutes.reduce(
    (acc, route) => {
      const prefix = route.prefix || "web"
      if (!acc[prefix]) acc[prefix] = []
      acc[prefix].push(route)
      return acc
    },
    {} as Record<string, Route[]>,
  )

  const selectRoute = (route: Route) => {
    setSelectedRoute(route)
    setRequest((prev) => ({
      ...prev,
      url: route.uri,
      method: route.method,
    }))
  }

  const sendRequest = async () => {
    if (!request.url) return

    setLoading(true)
    const startTime = Date.now()

    try {
      let url = request.url
      if (request.params.length > 0) {
        const params = new URLSearchParams(
          request.params
            .filter((p) => p.key && p.value)
            .map((p) => [p.key, p.value]),
        )
        url += `?${params.toString()}`
      }

      const headers: Record<string, string> = {}
      request.headers
        .filter((h) => h.key && h.value)
        .forEach((h) => {
          headers[h.key] = h.value
        })

      if (request.auth.type === "bearer" && request.auth.token) {
        headers["Authorization"] = `Bearer ${request.auth.token}`
      } else if (
        request.auth.type === "basic" &&
        request.auth.username &&
        request.auth.password
      ) {
        const encoded = btoa(
          `${request.auth.username}:${request.auth.password}`,
        )
        headers["Authorization"] = `Basic ${encoded}`
      }

      let body: string | FormData | undefined
      if (["POST", "PUT", "PATCH"].includes(request.method)) {
        if (request.bodyType === "json") {
          headers["Content-Type"] = "application/json"
          body = request.body
        } else {
          const formData = new FormData()
          request.formData
            .filter((f) => f.key && f.value)
            .forEach((f) => formData.append(f.key, f.value))
          body = formData
        }
      }

      const res = await fetch(url, {
        method: request.method,
        headers,
        body,
      })

      const time = Date.now() - startTime
      const contentType = res.headers.get("content-type") || ""
      const isJson = contentType.includes("application/json")
      const isHtml = contentType.includes("text/html")
      const isText = contentType.includes("text/") && !isHtml

      let data: any
      if (isJson) {
        try {
          data = await res.json()
        } catch {
          data = await res.text()
        }
      } else {
        data = await res.text()
      }

      const responseHeaders: Record<string, string> = {}
      res.headers.forEach((value, key) => {
        responseHeaders[key] = value
      })

      setResponse({
        status: res.status,
        statusText: res.statusText,
        data,
        headers: responseHeaders,
        time,
        contentType,
        isHtml,
        isJson,
        isText,
      })
      setResponseTab("body")
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : "An unknown error occurred"
      setResponse({
        status: 0,
        statusText: "Error",
        data: errorMessage,
        headers: {},
        time: Date.now() - startTime,
        contentType: "text/plain",
        isHtml: false,
        isJson: false,
        isText: true,
      })
    } finally {
      setLoading(false)
    }
  }

  const getMethodColor = (method: string) => {
    const colors: Record<string, string> = {
      GET: "text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400",
      POST: "text-blue-600 bg-blue-50 dark:bg-blue-950 dark:text-blue-400",
      PUT: "text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400",
      PATCH: "text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400",
      DELETE: "text-red-600 bg-red-50 dark:bg-red-950 dark:text-red-400",
    }
    return colors[method] || "text-muted-foreground bg-muted"
  }

  const getStatusColor = (status: number) => {
    if (status >= 200 && status < 300)
      return "text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400"
    if (status >= 300 && status < 400)
      return "text-blue-600 bg-blue-50 dark:bg-blue-950 dark:text-blue-400"
    if (status >= 400 && status < 500)
      return "text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400"
    return "text-red-600 bg-red-50 dark:bg-red-950 dark:text-red-400"
  }

  const getDeviceWidth = (size: DeviceSize): string => {
    const widths = {
      mobile: "375px",
      tablet: "768px",
      desktop: "100%",
    }
    return widths[size]
  }

  const renderResponseBody = () => {
    if (!response) return null

    if (response.isHtml) {
      return (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <Label>Device Preview</Label>
            <div className="flex gap-2">
              <Button
                variant={deviceSize === "mobile" ? "default" : "outline"}
                size="sm"
                onClick={() => setDeviceSize("mobile")}
              >
                <Smartphone className="h-4 w-4 mr-1" />
                Mobile
              </Button>
              <Button
                variant={deviceSize === "tablet" ? "default" : "outline"}
                size="sm"
                onClick={() => setDeviceSize("tablet")}
              >
                <Tablet className="h-4 w-4 mr-1" />
                Tablet
              </Button>
              <Button
                variant={deviceSize === "desktop" ? "default" : "outline"}
                size="sm"
                onClick={() => setDeviceSize("desktop")}
              >
                <Monitor className="h-4 w-4 mr-1" />
                Desktop
              </Button>
            </div>
          </div>
          <div className="bg-muted p-4 rounded-md flex justify-center">
            <div
              style={{
                width: getDeviceWidth(deviceSize),
                maxWidth: "100%",
                transition: "width 0.3s ease",
              }}
            >
              <div className="bg-background shadow-lg rounded-lg overflow-hidden border">
                <div className="bg-muted text-muted-foreground text-xs px-3 py-1 flex items-center justify-between border-b">
                  <span>
                    {deviceSize.charAt(0).toUpperCase() + deviceSize.slice(1)}{" "}
                    View
                  </span>
                  <span>{getDeviceWidth(deviceSize)}</span>
                </div>
                <iframe
                  ref={iframeRef}
                  srcDoc={response.data}
                  className="w-full border-0"
                  style={{ height: "600px" }}
                  sandbox="allow-same-origin allow-scripts"
                  title="Response Preview"
                />
              </div>
            </div>
          </div>
        </div>
      )
    }

    if (response.isJson && typeof response.data === "object") {
      return (
        <div className="overflow-y-auto overflow-x-hidden max-h-96 rounded-md">
          <SyntaxHighlighter
            language="json"
            style={isDarkMode ? vscDarkPlus : vs}
            customStyle={{
              margin: 0,
              borderRadius: "0.375rem",
              fontSize: "0.875rem",
              maxWidth: "100%",
              overflowX: "hidden",
              wordBreak: "break-word",
              whiteSpace: "pre-wrap",
            }}
            wrapLines={true}
            wrapLongLines={true}
            PreTag="div"
          >
            {JSON.stringify(response.data, null, 2)}
          </SyntaxHighlighter>
        </div>
      )
    }

    if (typeof response.data === "string") {
      try {
        const parsed = JSON.parse(response.data)
        return (
          <div className="overflow-y-auto overflow-x-hidden max-h-96 rounded-md">
            <SyntaxHighlighter
              language="json"
              style={isDarkMode ? vscDarkPlus : vs}
              customStyle={{
                margin: 0,
                borderRadius: "0.375rem",
                fontSize: "0.875rem",
                maxWidth: "100%",
                overflowX: "hidden",
                wordBreak: "break-word",
                whiteSpace: "pre-wrap",
              }}
              wrapLines={true}
              wrapLongLines={true}
              PreTag="div"
            >
              {JSON.stringify(parsed, null, 2)}
            </SyntaxHighlighter>
          </div>
        )
      } catch {
        return (
          <div className="bg-muted p-4 rounded-md overflow-y-auto overflow-x-hidden max-h-96">
            <pre className="text-sm font-mono whitespace-pre-wrap break-words">
              {response.data}
            </pre>
          </div>
        )
      }
    }

    return (
      <div className="bg-muted p-4 rounded-md overflow-y-auto overflow-x-hidden max-h-96">
        <pre className="text-sm font-mono whitespace-pre-wrap break-words">
          {String(response.data)}
        </pre>
      </div>
    )
  }

  return (
    <SidebarProvider>
      <div className="flex h-screen w-full">
        {/* Sidebar with routes */}
        <Sidebar>
          <SidebarContent>
            <SidebarGroup>
              <SidebarGroupLabel>API Routes</SidebarGroupLabel>
              <SidebarGroupContent>
                <div className="px-2 pb-2">
                  <div className="relative">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search routes..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-8"
                    />
                  </div>
                </div>
                <SidebarMenu>
                  {Object.entries(groupedRoutes).map(([prefix, routes]) => (
                    <div key={prefix}>
                      {prefix !== "web" && (
                        <div className="px-2 py-1">
                          <span className="text-xs font-semibold text-muted-foreground uppercase">
                            {prefix}
                          </span>
                        </div>
                      )}
                      {routes.map((route, idx) => (
                        <SidebarMenuItem
                          key={`${route.method}-${route.uri}-${idx}`}
                        >
                          <SidebarMenuButton
                            onClick={() => selectRoute(route)}
                            isActive={
                              selectedRoute?.uri === route.uri &&
                              selectedRoute?.method === route.method
                            }
                          >
                            <span
                              className={`text-xs font-semibold px-2 py-0.5 rounded ${getMethodColor(route.method)}`}
                            >
                              {route.method}
                            </span>
                            <span className="truncate text-sm">
                              {route.uri}
                            </span>
                          </SidebarMenuButton>
                        </SidebarMenuItem>
                      ))}
                    </div>
                  ))}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          </SidebarContent>
        </Sidebar>

        {/* Main content */}
        <SidebarInset>
          <div className="flex flex-col h-full">
            {/* Header */}
            <header className="flex h-14 items-center gap-4 border-b bg-background px-6">
              <SidebarTrigger />
              <Separator orientation="vertical" className="h-6" />
              <h1 className="text-lg font-semibold">API Playground</h1>
              <div className="ml-auto">
                <ThemeToggle />
              </div>
            </header>

            {/* Request Panel */}
            <div className="flex-1 overflow-auto p-6 space-y-6">
              <div className="space-y-4">
                <div className="flex gap-2">
                  <Select
                    value={request.method}
                    onValueChange={(value) =>
                      setRequest({ ...request, method: value })
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
                    placeholder="Enter URL..."
                    value={request.url}
                    onChange={(e) =>
                      setRequest({ ...request, url: e.target.value })
                    }
                    className="flex-1"
                  />
                  <Button onClick={sendRequest} disabled={loading}>
                    <Send className="h-4 w-4 mr-2" />
                    {loading ? "Sending..." : "Send"}
                  </Button>
                </div>

                <Tabs defaultValue="params">
                  <TabsList>
                    <TabsTrigger value="params">Params</TabsTrigger>
                    <TabsTrigger value="headers">Headers</TabsTrigger>
                    <TabsTrigger value="auth">Authorization</TabsTrigger>
                    <TabsTrigger value="body">Body</TabsTrigger>
                  </TabsList>

                  <TabsContent value="params" className="space-y-2">
                    {request.params.map((param, idx) => (
                      <div key={idx} className="flex gap-2">
                        <Input
                          placeholder="Key"
                          value={param.key}
                          onChange={(e) => {
                            const newParams = [...request.params]
                            newParams[idx].key = e.target.value
                            setRequest({ ...request, params: newParams })
                          }}
                        />
                        <Input
                          placeholder="Value"
                          value={param.value}
                          onChange={(e) => {
                            const newParams = [...request.params]
                            newParams[idx].value = e.target.value
                            setRequest({ ...request, params: newParams })
                          }}
                        />
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            const newParams = request.params.filter(
                              (_, i) => i !== idx,
                            )
                            setRequest({ ...request, params: newParams })
                          }}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        setRequest({
                          ...request,
                          params: [...request.params, { key: "", value: "" }],
                        })
                      }
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Add Param
                    </Button>
                  </TabsContent>

                  <TabsContent value="headers" className="space-y-2">
                    {request.headers.map((header, idx) => (
                      <div key={idx} className="flex gap-2">
                        <div className="flex-1">
                          <Input
                            placeholder="Header Name"
                            value={header.key}
                            list={`header-keys-${idx}`}
                            onChange={(e) => {
                              const newHeaders = [...request.headers]
                              newHeaders[idx].key = e.target.value
                              setRequest({ ...request, headers: newHeaders })
                            }}
                          />
                          <datalist id={`header-keys-${idx}`}>
                            {COMMON_HEADERS.map((headerName) => (
                              <option key={headerName} value={headerName} />
                            ))}
                          </datalist>
                        </div>
                        <div className="flex-1">
                          <Input
                            placeholder="Value"
                            value={header.value}
                            list={`header-values-${idx}`}
                            onChange={(e) => {
                              const newHeaders = [...request.headers]
                              newHeaders[idx].value = e.target.value
                              setRequest({ ...request, headers: newHeaders })
                            }}
                          />
                          <datalist id={`header-values-${idx}`}>
                            {header.key &&
                              HEADER_SUGGESTIONS[header.key]?.map((value) => (
                                <option key={value} value={value} />
                              ))}
                          </datalist>
                        </div>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            const newHeaders = request.headers.filter(
                              (_, i) => i !== idx,
                            )
                            setRequest({ ...request, headers: newHeaders })
                          }}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        setRequest({
                          ...request,
                          headers: [...request.headers, { key: "", value: "" }],
                        })
                      }
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Add Header
                    </Button>
                  </TabsContent>

                  <TabsContent value="auth" className="space-y-4">
                    <Select
                      value={request.auth.type}
                      onValueChange={(value) =>
                        setRequest({
                          ...request,
                          auth: {
                            ...request.auth,
                            type: value as "none" | "bearer" | "basic",
                          },
                        })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent align="start">
                        <SelectItem value="none">None</SelectItem>
                        <SelectItem value="bearer">Bearer Token</SelectItem>
                        <SelectItem value="basic">Basic Auth</SelectItem>
                      </SelectContent>
                    </Select>

                    {request.auth.type === "bearer" && (
                      <div className="space-y-2">
                        <Label>Token</Label>
                        <Input
                          placeholder="Enter bearer token..."
                          value={request.auth.token}
                          onChange={(e) =>
                            setRequest({
                              ...request,
                              auth: { ...request.auth, token: e.target.value },
                            })
                          }
                        />
                      </div>
                    )}

                    {request.auth.type === "basic" && (
                      <>
                        <div className="space-y-2">
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
                        <div className="space-y-2">
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
                      </>
                    )}
                  </TabsContent>

                  <TabsContent value="body" className="space-y-4">
                    <Select
                      value={request.bodyType}
                      onValueChange={(value) =>
                        setRequest({
                          ...request,
                          bodyType: value as "json" | "form",
                        })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent align="start">
                        <SelectItem value="json">JSON</SelectItem>
                        <SelectItem value="form">Form Data</SelectItem>
                      </SelectContent>
                    </Select>

                    {request.bodyType === "json" && (
                      <Textarea
                        placeholder='{"key": "value"}'
                        value={request.body}
                        onChange={(e) =>
                          setRequest({ ...request, body: e.target.value })
                        }
                        rows={10}
                        className="font-mono text-sm"
                      />
                    )}

                    {request.bodyType === "form" && (
                      <div className="space-y-2">
                        {request.formData.map((field, idx) => (
                          <div key={idx} className="flex gap-2">
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
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        ))}
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() =>
                            setRequest({
                              ...request,
                              formData: [
                                ...request.formData,
                                { key: "", value: "" },
                              ],
                            })
                          }
                        >
                          <Plus className="h-4 w-4 mr-2" />
                          Add Field
                        </Button>
                      </div>
                    )}
                  </TabsContent>
                </Tabs>
              </div>

              {/* Response Panel */}
              {response && (
                <div className="border rounded-lg overflow-hidden">
                  <div className="bg-muted px-4 py-3 border-b flex items-center justify-between">
                    <h3 className="font-semibold">Response</h3>
                    <div className="flex items-center gap-3">
                      <span
                        className={`text-sm font-semibold px-3 py-1 rounded ${getStatusColor(response.status)}`}
                      >
                        {response.status} {response.statusText}
                      </span>
                      <span className="text-sm text-muted-foreground">
                        {response.time}ms
                      </span>
                    </div>
                  </div>
                  <div className="p-4 overflow-x-hidden">
                    <Tabs value={responseTab} onValueChange={setResponseTab}>
                      <TabsList>
                        <TabsTrigger value="body">Body</TabsTrigger>
                        <TabsTrigger value="headers">Headers</TabsTrigger>
                        {response.isHtml && (
                          <TabsTrigger value="raw">Raw HTML</TabsTrigger>
                        )}
                      </TabsList>
                      <TabsContent value="body">
                        {renderResponseBody()}
                      </TabsContent>
                      <TabsContent value="headers">
                        <div className="space-y-2">
                          {Object.entries(response.headers).map(
                            ([key, value]) => (
                              <div key={key} className="flex gap-2 text-sm">
                                <span className="font-semibold min-w-[200px]">
                                  {key}:
                                </span>
                                <span className="text-muted-foreground">
                                  {value}
                                </span>
                              </div>
                            ),
                          )}
                        </div>
                      </TabsContent>
                      <TabsContent value="raw">
                        <div className="overflow-y-auto overflow-x-hidden max-h-96 rounded-md">
                          <SyntaxHighlighter
                            language="html"
                            style={isDarkMode ? vscDarkPlus : vs}
                            customStyle={{
                              margin: 0,
                              borderRadius: "0.375rem",
                              fontSize: "0.875rem",
                              maxWidth: "100%",
                              overflowX: "hidden",
                              wordBreak: "break-word",
                              whiteSpace: "pre-wrap",
                            }}
                            wrapLines={true}
                            wrapLongLines={true}
                            PreTag="div"
                          >
                            {typeof response.data === "string"
                              ? response.data
                              : JSON.stringify(response.data, null, 2)}
                          </SyntaxHighlighter>
                        </div>
                      </TabsContent>
                    </Tabs>
                  </div>
                </div>
              )}
            </div>
          </div>
        </SidebarInset>
      </div>
    </SidebarProvider>
  )
}
