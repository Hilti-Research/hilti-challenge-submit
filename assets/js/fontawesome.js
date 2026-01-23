import { config, dom, library } from '@fortawesome/fontawesome-svg-core'
import {
  faEnvelopeOpen,
  faFileArchive,
  faFilePdf
} from '@fortawesome/free-regular-svg-icons'
import {
  faArrowsRotate
} from '@fortawesome/free-solid-svg-icons'
import '@fortawesome/fontawesome-svg-core/styles.css'

// configure fontawesome
config.autoAddCss = false
library.add(
  faFilePdf,
  faFileArchive,
  faEnvelopeOpen,
  faArrowsRotate
)
dom.watch()
