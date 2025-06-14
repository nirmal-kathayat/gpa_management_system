import "./bootstrap"
import bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js"

// Global JavaScript for GPA System
document.addEventListener("DOMContentLoaded", () => {
  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert:not(.alert-permanent)")
  alerts.forEach((alert) => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert)
      bsAlert.close()
    }, 5000)
  })

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll("[data-confirm-delete]")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const message = this.getAttribute("data-confirm-delete") || "Are you sure you want to delete this item?"
      if (!confirm(message)) {
        e.preventDefault()
      }
    })
  })

  // Form validation enhancements
  const forms = document.querySelectorAll(".needs-validation")
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!form.checkValidity()) {
        e.preventDefault()
        e.stopPropagation()
      }
      form.classList.add("was-validated")
    })
  })

  // Auto-calculate GPA in report forms
  const markInputs = document.querySelectorAll('input[name*="marks"]')
  markInputs.forEach((input) => {
    input.addEventListener("input", calculateRowTotal)
  })

  // Print functionality
  window.printPage = () => {
    window.print()
  }

  // Export functionality
  window.exportTable = (tableId, filename = "export") => {
    const table = document.getElementById(tableId)
    if (table) {
      const csv = tableToCSV(table)
      downloadCSV(csv, filename + ".csv")
    }
  }

  // Search functionality
  const searchInputs = document.querySelectorAll("[data-search-table]")
  searchInputs.forEach((input) => {
    input.addEventListener("input", function () {
      const tableId = this.getAttribute("data-search-table")
      const table = document.getElementById(tableId)
      if (table) {
        searchTable(table, this.value)
      }
    })
  })

  // Tooltips initialization
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Popovers initialization
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))
})

// Helper functions
function calculateRowTotal() {
  const row = this.closest("tr")
  if (row) {
    const inputs = row.querySelectorAll('input[type="number"]')
    let total = 0
    inputs.forEach((input) => {
      if (input.value) {
        total += Number.parseFloat(input.value)
      }
    })

    const totalCell = row.querySelector(".total-marks")
    if (totalCell) {
      totalCell.textContent = total.toFixed(2)
    }
  }
}

function tableToCSV(table) {
  const rows = table.querySelectorAll("tr")
  const csv = []

  rows.forEach((row) => {
    const cols = row.querySelectorAll("td, th")
    const rowData = []
    cols.forEach((col) => {
      rowData.push('"' + col.textContent.replace(/"/g, '""') + '"')
    })
    csv.push(rowData.join(","))
  })

  return csv.join("\n")
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: "text/csv" })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.setAttribute("hidden", "")
  a.setAttribute("href", url)
  a.setAttribute("download", filename)
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
}

function searchTable(table, searchTerm) {
  const rows = table.querySelectorAll("tbody tr")
  const term = searchTerm.toLowerCase()

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase()
    if (text.includes(term)) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })
}

// Global utility functions
window.GPA = {
  // Format number to 2 decimal places
  formatNumber: (num) => Number.parseFloat(num).toFixed(2),

  // Calculate GPA from marks
  calculateGPA: (marks, gradeSystem) => {
    let totalPoints = 0
    let totalSubjects = 0

    marks.forEach((mark) => {
      const grade = gradeSystem.find((g) => mark >= g.marks_from && mark <= g.marks_to)
      if (grade) {
        totalPoints += grade.grade_point
        totalSubjects++
      }
    })

    return totalSubjects > 0 ? (totalPoints / totalSubjects).toFixed(2) : "0.00"
  },

  // Show loading spinner
  showLoading: (element) => {
    element.innerHTML =
      '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>'
  },

  // Hide loading spinner
  hideLoading: (element, originalContent) => {
    element.innerHTML = originalContent
  },

  // Show toast notification
  showToast: (message, type = "success") => {
    const toastContainer = document.getElementById("toast-container") || createToastContainer()
    const toast = createToast(message, type)
    toastContainer.appendChild(toast)

    const bsToast = new bootstrap.Toast(toast)
    bsToast.show()

    toast.addEventListener("hidden.bs.toast", () => {
      toast.remove()
    })
  },
}

function createToastContainer() {
  const container = document.createElement("div")
  container.id = "toast-container"
  container.className = "toast-container position-fixed top-0 end-0 p-3"
  container.style.zIndex = "1055"
  document.body.appendChild(container)
  return container
}

function createToast(message, type) {
  const toast = document.createElement("div")
  toast.className = `toast align-items-center text-white bg-${type} border-0`
  toast.setAttribute("role", "alert")
  toast.setAttribute("aria-live", "assertive")
  toast.setAttribute("aria-atomic", "true")

  toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `

  return toast
}
