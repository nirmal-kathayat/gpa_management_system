import axios from "axios"
window.axios = axios

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

// Add CSRF token to all requests
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
  window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content
} else {
  console.error("CSRF token not found")
}

// Add loading states to forms
document.addEventListener("DOMContentLoaded", () => {
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", () => {
      const submitBtn = form.querySelector('button[type="submit"]')
      if (submitBtn) {
        submitBtn.disabled = true
        const originalText = submitBtn.innerHTML
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'

        // Re-enable after 10 seconds as fallback
        setTimeout(() => {
          submitBtn.disabled = false
          submitBtn.innerHTML = originalText
        }, 10000)
      }
    })
  })
})
