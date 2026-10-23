<template>
  <div id="app">
    <!-- Login Page -->
    <div v-if="!isLoggedIn" class="auth-container">
      <div class="auth-card">
        <div class="auth-logo">
          <div class="logo-icon">🔬</div>
          <h1 class="auth-title">Lab Inventory</h1>
          <p class="auth-subtitle">Sistem Manajemen Alat Laboratorium</p>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="loginForm.email"
              type="email"
              placeholder="admin@ugm.ac.id"
              required
              :disabled="loginLoading"
            />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input
              id="password"
              v-model="loginForm.password"
              type="password"
              placeholder="••••••••"
              required
              :disabled="loginLoading"
            />
          </div>

          <div v-if="loginError" class="alert alert-error">
            {{ loginError }}
          </div>

          <button type="submit" class="btn btn-primary btn-full" :disabled="loginLoading">
            <span v-if="loginLoading" class="spinner"></span>
            <span>{{ loginLoading ? 'Masuk...' : 'Masuk' }}</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Dashboard -->
    <div v-else class="dashboard">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <div class="logo-icon">🔬</div>
          <span class="sidebar-title">Lab Inventory</span>
        </div>
        <nav class="sidebar-nav">
          <a href="#" class="nav-item active">
            <span class="nav-icon">📦</span>
            <span>Peralatan</span>
          </a>
        </nav>
        <div class="sidebar-footer">
          <button @click="handleLogout" class="btn btn-ghost btn-full" :disabled="logoutLoading">
            <span>{{ logoutLoading ? '...' : '🚪 Logout' }}</span>
          </button>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <!-- Header -->
        <header class="content-header">
          <div>
            <h2 class="page-title">Manajemen Peralatan Lab</h2>
            <p class="page-subtitle">Kelola inventaris alat laboratorium Anda</p>
          </div>
          <button @click="openAddModal" class="btn btn-primary">
            <span>+ Tambah Alat</span>
          </button>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg,#667eea,#764ba2)">📦</div>
            <div class="stat-info">
              <div class="stat-value">{{ equipments.length }}</div>
              <div class="stat-label">Total Jenis Alat</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg,#11998e,#38ef7d)">✅</div>
            <div class="stat-info">
              <div class="stat-value">{{ countByCondition('baik') }}</div>
              <div class="stat-label">Kondisi Baik</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg,#f7971e,#ffd200)">⚠️</div>
            <div class="stat-info">
              <div class="stat-value">{{ countByCondition('rusak_ringan') }}</div>
              <div class="stat-label">Rusak Ringan</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg,#cb2d3e,#ef473a)">🔴</div>
            <div class="stat-info">
              <div class="stat-value">{{ countByCondition('rusak_berat') }}</div>
              <div class="stat-label">Rusak Berat</div>
            </div>
          </div>
        </div>

        <!-- Alert Messages -->
        <div v-if="alertMessage" :class="['alert', alertType === 'success' ? 'alert-success' : 'alert-error']">
          {{ alertMessage }}
        </div>

        <!-- Table -->
        <div class="table-card">
          <div class="table-header">
            <h3>Daftar Peralatan</h3>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Cari peralatan..."
              class="search-input"
            />
          </div>

          <div v-if="tableLoading" class="table-loading">
            <div class="spinner-large"></div>
            <p>Memuat data...</p>
          </div>

          <div v-else-if="filteredEquipments.length === 0" class="table-empty">
            <div class="empty-icon">📭</div>
            <p>Tidak ada data peralatan</p>
            <button @click="openAddModal" class="btn btn-primary">Tambah Alat Pertama</button>
          </div>

          <div v-else class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nama Alat</th>
                  <th>Kategori</th>
                  <th>Serial Number</th>
                  <th>Kondisi</th>
                  <th>Qty</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(eq, index) in filteredEquipments" :key="eq.id" class="table-row">
                  <td class="text-muted">{{ index + 1 }}</td>
                  <td class="font-medium">{{ eq.name }}</td>
                  <td>
                    <span class="badge badge-category">{{ eq.category }}</span>
                  </td>
                  <td class="text-mono">{{ eq.serial_number }}</td>
                  <td>
                    <span :class="['badge', conditionBadge(eq.condition)]">
                      {{ conditionLabel(eq.condition) }}
                    </span>
                  </td>
                  <td class="text-center font-medium">{{ eq.quantity }}</td>
                  <td>
                    <div class="action-buttons">
                      <button @click="openEditModal(eq)" class="btn btn-sm btn-edit" title="Edit">✏️</button>
                      <button @click="confirmDelete(eq)" class="btn btn-sm btn-delete" title="Hapus">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>

    <!-- Modal Add/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ editingEquipment ? 'Edit Peralatan' : 'Tambah Peralatan Baru' }}</h3>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="modal-form">
          <div class="form-row">
            <div class="form-group">
              <label>Nama Alat *</label>
              <input v-model="equipmentForm.name" type="text" placeholder="Nama alat laboratorium" required />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name[0] }}</span>
            </div>
            <div class="form-group">
              <label>Kategori *</label>
              <input v-model="equipmentForm.category" type="text" placeholder="Misal: Optik, Elektronik" required />
              <span v-if="formErrors.category" class="field-error">{{ formErrors.category[0] }}</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Serial Number *</label>
              <input v-model="equipmentForm.serial_number" type="text" placeholder="SN-XXXXX-2024" required />
              <span v-if="formErrors.serial_number" class="field-error">{{ formErrors.serial_number[0] }}</span>
            </div>
            <div class="form-group">
              <label>Jumlah *</label>
              <input v-model.number="equipmentForm.quantity" type="number" min="0" placeholder="1" required />
              <span v-if="formErrors.quantity" class="field-error">{{ formErrors.quantity[0] }}</span>
            </div>
          </div>

          <div class="form-group">
            <label>Kondisi *</label>
            <select v-model="equipmentForm.condition" required>
              <option value="">-- Pilih Kondisi --</option>
              <option value="baik">Baik</option>
              <option value="rusak_ringan">Rusak Ringan</option>
              <option value="rusak_berat">Rusak Berat</option>
            </select>
            <span v-if="formErrors.condition" class="field-error">{{ formErrors.condition[0] }}</span>
          </div>

          <div v-if="submitError" class="alert alert-error">{{ submitError }}</div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-ghost">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="submitLoading">
              <span v-if="submitLoading" class="spinner"></span>
              {{ submitLoading ? 'Menyimpan...' : (editingEquipment ? 'Simpan Perubahan' : 'Tambah Alat') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-card modal-small">
        <div class="modal-header">
          <h3>Konfirmasi Hapus</h3>
          <button @click="showDeleteModal = false" class="btn-close">✕</button>
        </div>
        <div class="delete-body">
          <div class="delete-icon">🗑️</div>
          <p>Apakah Anda yakin ingin menghapus <strong>{{ deletingEquipment?.name }}</strong>?</p>
          <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn btn-ghost">Batal</button>
          <button @click="handleDelete" class="btn btn-danger" :disabled="deleteLoading">
            {{ deleteLoading ? 'Menghapus...' : 'Ya, Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from './api/axios.js'

// =================== STATE ===================
const isLoggedIn = ref(!!localStorage.getItem('auth_token'))

// Login
const loginForm = ref({ email: '', password: '' })
const loginLoading = ref(false)
const loginError = ref('')

// Logout
const logoutLoading = ref(false)

// Equipments
const equipments = ref([])
const tableLoading = ref(false)
const searchQuery = ref('')

// Alert
const alertMessage = ref('')
const alertType = ref('success')

// Modal Add/Edit
const showModal = ref(false)
const editingEquipment = ref(null)
const equipmentForm = ref({ name: '', category: '', serial_number: '', condition: '', quantity: 1 })
const formErrors = ref({})
const submitLoading = ref(false)
const submitError = ref('')

// Delete
const showDeleteModal = ref(false)
const deletingEquipment = ref(null)
const deleteLoading = ref(false)

// =================== COMPUTED ===================
const filteredEquipments = computed(() => {
  if (!searchQuery.value) return equipments.value
  const q = searchQuery.value.toLowerCase()
  return equipments.value.filter(e =>
    e.name.toLowerCase().includes(q) ||
    e.category.toLowerCase().includes(q) ||
    e.serial_number.toLowerCase().includes(q)
  )
})

// =================== HELPERS ===================
function countByCondition(cond) {
  return equipments.value.filter(e => e.condition === cond).length
}

function conditionLabel(cond) {
  const map = { baik: 'Baik', rusak_ringan: 'Rusak Ringan', rusak_berat: 'Rusak Berat' }
  return map[cond] || cond
}

function conditionBadge(cond) {
  const map = { baik: 'badge-success', rusak_ringan: 'badge-warning', rusak_berat: 'badge-danger' }
  return map[cond] || ''
}

function showAlert(msg, type = 'success') {
  alertMessage.value = msg
  alertType.value = type
  setTimeout(() => { alertMessage.value = '' }, 4000)
}

// =================== AUTH ===================
async function handleLogin() {
  loginLoading.value = true
  loginError.value = ''
  try {
    const res = await apiClient.post('/login', loginForm.value)
    localStorage.setItem('auth_token', res.data.token)
    isLoggedIn.value = true
    await loadEquipments()
  } catch (err) {
    loginError.value = err.response?.data?.message || 'Email atau password salah.'
  } finally {
    loginLoading.value = false
  }
}

async function handleLogout() {
  logoutLoading.value = true
  try {
    await apiClient.post('/logout')
  } catch (e) { /* ignore */ }
  localStorage.removeItem('auth_token')
  isLoggedIn.value = false
  equipments.value = []
  logoutLoading.value = false
}

// =================== EQUIPMENTS CRUD ===================
async function loadEquipments() {
  tableLoading.value = true
  try {
    const res = await apiClient.get('/equipments')
    equipments.value = res.data.data || res.data
  } catch (err) {
    showAlert('Gagal memuat data peralatan.', 'error')
  } finally {
    tableLoading.value = false
  }
}

function openAddModal() {
  editingEquipment.value = null
  equipmentForm.value = { name: '', category: '', serial_number: '', condition: '', quantity: 1 }
  formErrors.value = {}
  submitError.value = ''
  showModal.value = true
}

function openEditModal(eq) {
  editingEquipment.value = eq
  equipmentForm.value = { ...eq }
  formErrors.value = {}
  submitError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingEquipment.value = null
}

async function handleSubmit() {
  submitLoading.value = true
  formErrors.value = {}
  submitError.value = ''
  try {
    if (editingEquipment.value) {
      const res = await apiClient.put(`/equipments/${editingEquipment.value.id}`, equipmentForm.value)
      const updated = res.data.data || res.data
      const idx = equipments.value.findIndex(e => e.id === updated.id)
      if (idx !== -1) equipments.value[idx] = updated
      showAlert('Peralatan berhasil diperbarui!')
    } else {
      const res = await apiClient.post('/equipments', equipmentForm.value)
      const created = res.data.data || res.data
      equipments.value.unshift(created)
      showAlert('Peralatan berhasil ditambahkan!')
    }
    closeModal()
  } catch (err) {
    if (err.response?.status === 422) {
      formErrors.value = err.response.data.errors || {}
      submitError.value = err.response.data.message || 'Validasi gagal, periksa kembali input Anda.'
    } else {
      submitError.value = err.response?.data?.message || 'Terjadi kesalahan. Coba lagi.'
    }
  } finally {
    submitLoading.value = false
  }
}

function confirmDelete(eq) {
  deletingEquipment.value = eq
  showDeleteModal.value = true
}

async function handleDelete() {
  deleteLoading.value = true
  try {
    await apiClient.delete(`/equipments/${deletingEquipment.value.id}`)
    equipments.value = equipments.value.filter(e => e.id !== deletingEquipment.value.id)
    showDeleteModal.value = false
    showAlert('Peralatan berhasil dihapus!')
  } catch (err) {
    showAlert('Gagal menghapus peralatan.', 'error')
  } finally {
    deleteLoading.value = false
  }
}

// =================== LIFECYCLE ===================
onMounted(() => {
  if (isLoggedIn.value) loadEquipments()
})
</script>
