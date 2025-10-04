import { useState, useEffect, useRef } from "react"
import { useQueryState } from "nuqs"
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
  X,
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

interface RequestTab {
  id: string
  label: string
  method: string
  uri: string
  request: RequestConfig
  response: ResponseData | null
  responseTab: string
  deviceSize: DeviceSize
  loading: boolean
}

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
  const [selectedRouteUri, setSelectedRouteUri] = useQueryState("route", {
    defaultValue: "",
  })
  const [selectedGroup, setSelectedGroup] = useQueryState("group", {
    defaultValue: "",
  })
  const [activeTabId, setActiveTabId] = useQueryState("tab", {
    defaultValue: "",
  })
  const [searchQuery, setSearchQuery] = useState("")
  const [tabs, setTabs] = useState<RequestTab[]>(() => {
    // Load tabs from localStorage
    const saved = localStorage.getItem("volcanic-tabs")
    if (saved) {
      try {
        return JSON.parse(saved)
      } catch {
        return []
      }
    }
    return []
  })
  const [isDarkMode, setIsDarkMode] = useState(false)
  const iframeRef = useRef<HTMLIFrameElement>(null)

  // Save tabs to localStorage whenever they change
  useEffect(() => {
    localStorage.setItem("volcanic-tabs", JSON.stringify(tabs))
  }, [tabs])

  // Get active tab
  const activeTab = tabs.find((t) => t.id === activeTabId) || tabs[0] || null

  // Helper functions for tab management
  const createTab = (route: Route): RequestTab => {
    return {
      id: `${route.method}:${route.uri}:${Date.now()}`,
      label: route.uri,
      method: route.method,
      uri: route.uri,
      request: {
        method: route.method,
        url: route.uri,
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
      },
      response: null,
      responseTab: "body",
      deviceSize: "desktop",
      loading: false,
    }
  }

  const openTab = (route: Route) => {
    // Check if tab already exists for this route
    const existingTab = tabs.find(
      (t) => t.method === route.method && t.uri === route.uri,
    )

    if (existingTab) {
      // Switch to existing tab
      setActiveTabId(existingTab.id)
      setSelectedRouteUri(`${route.method}:${route.uri}`)
    } else {
      // Create new tab
      const newTab = createTab(route)
      setTabs([...tabs, newTab])
      setActiveTabId(newTab.id)
      setSelectedRouteUri(`${route.method}:${route.uri}`)
    }
  }

  const closeTab = (tabId: string) => {
    const newTabs = tabs.filter((t) => t.id !== tabId)
    setTabs(newTabs)

    // If closing active tab, switch to another tab
    if (tabId === activeTabId) {
      const activeIndex = tabs.findIndex((t) => t.id === tabId)
      const nextTab = newTabs[activeIndex] || newTabs[activeIndex - 1] || null
      if (nextTab) {
        setActiveTabId(nextTab.id)
        setSelectedRouteUri(`${nextTab.method}:${nextTab.uri}`)
      } else {
        setActiveTabId("")
        setSelectedRouteUri("")
      }
    }
  }

  const updateActiveTab = (updates: Partial<RequestTab>) => {
    if (!activeTab) return
    setTabs(tabs.map((t) => (t.id === activeTab.id ? { ...t, ...updates } : t)))
  }

  const updateActiveTabRequest = (updates: Partial<RequestConfig>) => {
    if (!activeTab) return
    updateActiveTab({
      request: { ...activeTab.request, ...updates },
    })
  }

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

  // Group routes by prefix
  const routeGroups = schema.routes.reduce(
    (groups, route) => {
      const group = route.prefix || "web"
      if (!groups[group]) {
        groups[group] = []
      }
      groups[group].push(route)
      return groups
    },
    {} as Record<string, Route[]>,
  )

  const groupNames = Object.keys(routeGroups).sort()
  const hasMultipleGroups = groupNames.length > 1

  // Set default group if not set
  useEffect(() => {
    if (groupNames.length > 0 && !selectedGroup) {
      setSelectedGroup(groupNames[0])
    }
  }, [groupNames.length, selectedGroup, setSelectedGroup, groupNames])

  // Get selected route from URL
  const selectedRoute =
    schema.routes.find((r) => `${r.method}:${r.uri}` === selectedRouteUri) ||
    null

  const currentGroup = selectedGroup || groupNames[0] || "web"
  const filteredRoutes = (routeGroups[currentGroup] || []).filter(
    (route) =>
      route.uri.toLowerCase().includes(searchQuery.toLowerCase()) ||
      route.method.toLowerCase().includes(searchQuery.toLowerCase()) ||
      route.name?.toLowerCase().includes(searchQuery.toLowerCase()),
  )

  const selectRoute = (route: Route) => {
    openTab(route)
  }

  const sendRequest = async () => {
    if (!activeTab || !activeTab.request.url) return

    const request = activeTab.request
    const startTime = Date.now()

    // Set loading state
    updateActiveTab({ loading: true, response: null })

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

      updateActiveTab({
        response: {
          status: res.status,
          statusText: res.statusText,
          data,
          headers: responseHeaders,
          time,
          contentType,
          isHtml,
          isJson,
          isText,
        },
        responseTab: "body",
        loading: false,
      })
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : "An unknown error occurred"
      updateActiveTab({
        response: {
          status: 0,
          statusText: "Error",
          data: errorMessage,
          headers: {},
          time: Date.now() - startTime,
          contentType: "text/plain",
          isHtml: false,
          isJson: false,
          isText: true,
        },
        loading: false,
      })
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
    if (!activeTab?.response) return null

    const response = activeTab.response
    const deviceSize = activeTab.deviceSize

    if (response.isHtml) {
      return (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <Label>Device Preview</Label>
            <div className="flex gap-2">
              <Button
                variant={deviceSize === "mobile" ? "default" : "outline"}
                size="sm"
                onClick={() => updateActiveTab({ deviceSize: "mobile" })}
              >
                <Smartphone className="h-4 w-4 mr-1" />
                Mobile
              </Button>
              <Button
                variant={deviceSize === "tablet" ? "default" : "outline"}
                size="sm"
                onClick={() => updateActiveTab({ deviceSize: "tablet" })}
              >
                <Tablet className="h-4 w-4 mr-1" />
                Tablet
              </Button>
              <Button
                variant={deviceSize === "desktop" ? "default" : "outline"}
                size="sm"
                onClick={() => updateActiveTab({ deviceSize: "desktop" })}
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
              <SidebarGroupLabel>
                {hasMultipleGroups ? "Routes" : "API Routes"}
              </SidebarGroupLabel>
              <SidebarGroupContent>
                {/* Group tabs - only show if multiple groups */}
                {hasMultipleGroups && (
                  <Tabs
                    value={currentGroup}
                    onValueChange={setSelectedGroup}
                    className="w-full"
                  >
                    <TabsList className="w-full grid grid-cols-auto gap-1 h-auto p-1">
                      {groupNames.map((group) => (
                        <TabsTrigger
                          key={group}
                          value={group}
                          className="capitalize text-xs"
                        >
                          {group}
                          <span className="ml-1 text-muted-foreground">
                            ({routeGroups[group]?.length || 0})
                          </span>
                        </TabsTrigger>
                      ))}
                    </TabsList>
                  </Tabs>
                )}

                {/* Search */}
                <div className="px-2 pb-2 pt-2">
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

                {/* Routes list */}
                <SidebarMenu>
                  {filteredRoutes.map((route, idx) => (
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
                        <span className="truncate text-sm">{route.uri}</span>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
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
              <SidebarTrigger className="md:hidden" />
              <Separator orientation="vertical" className="h-6 md:hidden" />

              {/* Tab Bar */}
              {tabs.length > 0 ? (
                <div className="flex-1 flex items-center gap-2 overflow-x-auto">
                  {tabs.map((tab) => (
                    <div
                      key={tab.id}
                      onClick={() => setActiveTabId(tab.id)}
                      className={`flex items-center gap-2 px-3 py-1.5 rounded-md cursor-pointer transition-colors ${
                        tab.id === activeTabId
                          ? "bg-primary text-primary-foreground"
                          : "bg-muted hover:bg-muted/80"
                      }`}
                    >
                      <span
                        className={`text-xs font-semibold px-1.5 py-0.5 rounded ${getMethodColor(tab.method)}`}
                      >
                        {tab.method}
                      </span>
                      <span className="text-sm truncate max-w-[200px]">
                        {tab.uri}
                      </span>
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          closeTab(tab.id)
                        }}
                        className="hover:bg-background/20 rounded-sm p-0.5"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <h1 className="text-lg font-semibold">API Playground</h1>
              )}

              <div className="ml-auto">
                <ThemeToggle />
              </div>
            </header>

            {/* Request Panel */}
            <div className="flex-1 overflow-auto p-6 space-y-6">
              {!activeTab ? (
                <div className="flex items-center justify-center h-full text-muted-foreground">
                  <p>Select a route from the sidebar to get started</p>
                </div>
              ) : (
                <>
                  <div className="space-y-4">
                    <div className="flex gap-2">
                      <Select
                        value={activeTab.request.method}
                        onValueChange={(value) =>
                          updateActiveTabRequest({ method: value })
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
                        value={activeTab.request.url}
                        onChange={(e) =>
                          updateActiveTabRequest({ url: e.target.value })
                        }
                        className="flex-1"
                      />
                      <Button
                        onClick={sendRequest}
                        disabled={activeTab.loading}
                      >
                        <Send className="h-4 w-4 mr-2" />
                        {activeTab.loading ? "Sending..." : "Send"}
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
                        {activeTab.request.params.map((param, idx) => (
                          <div key={idx} className="flex gap-2">
                            <Input
                              placeholder="Key"
                              value={param.key}
                              onChange={(e) => {
                                const newParams = [...activeTab.request.params]
                                newParams[idx].key = e.target.value
                                updateActiveTabRequest({ params: newParams })
                              }}
                            />
                            <Input
                              placeholder="Value"
                              value={param.value}
                              onChange={(e) => {
                                const newParams = [...activeTab.request.params]
                                newParams[idx].value = e.target.value
                                updateActiveTabRequest({ params: newParams })
                              }}
                            />
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => {
                                const newParams =
                                  activeTab.request.params.filter(
                                    (_, i) => i !== idx,
                                  )
                                updateActiveTabRequest({ params: newParams })
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
                            updateActiveTabRequest({
                              params: [
                                ...activeTab.request.params,
                                { key: "", value: "" },
                              ],
                            })
                          }
                        >
                          <Plus className="h-4 w-4 mr-2" />
                          Add Param
                        </Button>
                      </TabsContent>

                      <TabsContent value="headers" className="space-y-2">
                        {activeTab.request.headers.map((header, idx) => (
                          <div key={idx} className="flex gap-2">
                            <div className="flex-1">
                              <Input
                                placeholder="Header Name"
                                value={header.key}
                                list={`header-keys-${idx}`}
                                onChange={(e) => {
                                  const newHeaders = [
                                    ...activeTab.request.headers,
                                  ]
                                  newHeaders[idx].key = e.target.value
                                  updateActiveTabRequest({
                                    headers: newHeaders,
                                  })
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
                                  const newHeaders = [
                                    ...activeTab.request.headers,
                                  ]
                                  newHeaders[idx].value = e.target.value
                                  updateActiveTabRequest({
                                    headers: newHeaders,
                                  })
                                }}
                              />
                              <datalist id={`header-values-${idx}`}>
                                {header.key &&
                                  HEADER_SUGGESTIONS[header.key]?.map(
                                    (value) => (
                                      <option key={value} value={value} />
                                    ),
                                  )}
                              </datalist>
                            </div>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => {
                                const newHeaders =
                                  activeTab.request.headers.filter(
                                    (_, i) => i !== idx,
                                  )
                                updateActiveTabRequest({ headers: newHeaders })
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
                            updateActiveTabRequest({
                              headers: [
                                ...activeTab.request.headers,
                                { key: "", value: "" },
                              ],
                            })
                          }
                        >
                          <Plus className="h-4 w-4 mr-2" />
                          Add Header
                        </Button>
                      </TabsContent>

                      <TabsContent value="auth" className="space-y-4">
                        <Select
                          value={activeTab.request.auth.type}
                          onValueChange={(value) =>
                            updateActiveTabRequest({
                              auth: {
                                ...activeTab.request.auth,
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

                        {activeTab.request.auth.type === "bearer" && (
                          <div className="space-y-2">
                            <Label>Token</Label>
                            <Input
                              placeholder="Enter bearer token..."
                              value={activeTab.request.auth.token}
                              onChange={(e) =>
                                updateActiveTabRequest({
                                  auth: {
                                    ...activeTab.request.auth,
                                    token: e.target.value,
                                  },
                                })
                              }
                            />
                          </div>
                        )}

                        {activeTab.request.auth.type === "basic" && (
                          <>
                            <div className="space-y-2">
                              <Label>Username</Label>
                              <Input
                                placeholder="Username"
                                value={activeTab.request.auth.username}
                                onChange={(e) =>
                                  updateActiveTabRequest({
                                    auth: {
                                      ...activeTab.request.auth,
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
                                value={activeTab.request.auth.password}
                                onChange={(e) =>
                                  updateActiveTabRequest({
                                    auth: {
                                      ...activeTab.request.auth,
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
                          value={activeTab.request.bodyType}
                          onValueChange={(value) =>
                            updateActiveTabRequest({
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

                        {activeTab.request.bodyType === "json" && (
                          <Textarea
                            placeholder='{"key": "value"}'
                            value={activeTab.request.body}
                            onChange={(e) =>
                              updateActiveTabRequest({ body: e.target.value })
                            }
                            rows={10}
                            className="font-mono text-sm"
                          />
                        )}

                        {activeTab.request.bodyType === "form" && (
                          <div className="space-y-2">
                            {activeTab.request.formData.map((field, idx) => (
                              <div key={idx} className="flex gap-2">
                                <Input
                                  placeholder="Key"
                                  value={field.key}
                                  onChange={(e) => {
                                    const newFormData = [
                                      ...activeTab.request.formData,
                                    ]
                                    newFormData[idx].key = e.target.value
                                    updateActiveTabRequest({
                                      formData: newFormData,
                                    })
                                  }}
                                />
                                <Input
                                  placeholder="Value"
                                  value={field.value}
                                  onChange={(e) => {
                                    const newFormData = [
                                      ...activeTab.request.formData,
                                    ]
                                    newFormData[idx].value = e.target.value
                                    updateActiveTabRequest({
                                      formData: newFormData,
                                    })
                                  }}
                                />
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  onClick={() => {
                                    const newFormData =
                                      activeTab.request.formData.filter(
                                        (_, i) => i !== idx,
                                      )
                                    updateActiveTabRequest({
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
                                updateActiveTabRequest({
                                  formData: [
                                    ...activeTab.request.formData,
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
                  {activeTab.response && (
                    <div className="border rounded-lg overflow-hidden">
                      <div className="bg-muted px-4 py-3 border-b flex items-center justify-between">
                        <h3 className="font-semibold">Response</h3>
                        <div className="flex items-center gap-3">
                          <span
                            className={`text-sm font-semibold px-3 py-1 rounded ${getStatusColor(activeTab.response.status)}`}
                          >
                            {activeTab.response.status}{" "}
                            {activeTab.response.statusText}
                          </span>
                          <span className="text-sm text-muted-foreground">
                            {activeTab.response.time}ms
                          </span>
                        </div>
                      </div>
                      <div className="p-4 overflow-x-hidden">
                        <Tabs
                          value={activeTab.responseTab}
                          onValueChange={(value) =>
                            updateActiveTab({ responseTab: value })
                          }
                        >
                          <TabsList>
                            <TabsTrigger value="body">Body</TabsTrigger>
                            <TabsTrigger value="headers">Headers</TabsTrigger>
                            {activeTab.response.isHtml && (
                              <TabsTrigger value="raw">Raw HTML</TabsTrigger>
                            )}
                          </TabsList>
                          <TabsContent value="body">
                            {renderResponseBody()}
                          </TabsContent>
                          <TabsContent value="headers">
                            <div className="space-y-2">
                              {Object.entries(activeTab.response.headers).map(
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
                                {typeof activeTab.response.data === "string"
                                  ? activeTab.response.data
                                  : JSON.stringify(
                                      activeTab.response.data,
                                      null,
                                      2,
                                    )}
                              </SyntaxHighlighter>
                            </div>
                          </TabsContent>
                        </Tabs>
                      </div>
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        </SidebarInset>
      </div>
    </SidebarProvider>
  )
}
