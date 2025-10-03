import React from "react"
import ReactDOM from "react-dom/client"
import Playground from "./components/Playground"
import "../css/playground.css"

// Mount React app
const root = document.getElementById("volcanic-playground-root")
if (root) {
  ReactDOM.createRoot(root).render(
    <React.StrictMode>
      <Playground />
    </React.StrictMode>,
  )
}
