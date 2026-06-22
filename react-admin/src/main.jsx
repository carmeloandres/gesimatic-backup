import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { GesimaticBackupApp } from './GesimaticBackupApp.jsx' 

createRoot(document.getElementById('gesimatic-backup-admin')).render(
  <StrictMode>
    <GesimaticBackupApp />
  </StrictMode>,
)
