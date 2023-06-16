import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-text-with-values', IndexField)
  app.component('detail-text-with-values', DetailField)
  app.component('form-text-with-values', FormField)
})
