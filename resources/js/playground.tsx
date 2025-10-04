import React from "react"
import ReactDOM from "react-dom/client"
import { NuqsAdapter } from "nuqs/adapters/react"
import Playground from "./components/Playground"
import { ThemeProvider } from "./components/theme-provider"
import "../css/playground.css"

// Mount React app
const root = document.getElementById("volcanic-playground-root")
if (root) {
  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <NuqsAdapter>
        <ThemeProvider defaultTheme="system" storageKey="volcanic-ui-theme">
          <Playground />
        </ThemeProvider>
      </NuqsAdapter>
    </React.StrictMode>,
  )
}
