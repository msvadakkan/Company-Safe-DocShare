package com.company.safedocshare

import android.content.Intent
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.view.View
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.company.safedocshare.adapters.PdfAdapter
import com.company.safedocshare.api.ApiClient
import com.company.safedocshare.databinding.ActivityPdfListBinding
import com.company.safedocshare.models.PdfFile
import com.company.safedocshare.utils.PrefsManager
import com.company.safedocshare.utils.SecureActivity
import kotlinx.coroutines.launch

class PdfListActivity : SecureActivity() {

    private lateinit var binding: ActivityPdfListBinding
    private lateinit var adapter: PdfAdapter
    private var allPdfs: List<PdfFile> = emptyList()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPdfListBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setSupportActionBar(binding.toolbar)

        adapter = PdfAdapter { pdf -> openPdf(pdf) }

        binding.recyclerView.apply {
            layoutManager = LinearLayoutManager(this@PdfListActivity)
            adapter = this@PdfListActivity.adapter
        }

        binding.swipeRefresh.setOnRefreshListener { loadPdfs() }

        binding.btnLogout.setOnClickListener { logout() }

        binding.etSearch.addTextChangedListener(object : TextWatcher {
            override fun afterTextChanged(s: Editable?) { filterPdfs(s?.toString() ?: "") }
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {}
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
        })

        loadPdfs()
    }

    private fun loadPdfs() {
        val token = PrefsManager.getToken(this) ?: run { logout(); return }

        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = ApiClient.service.getPdfs("Bearer $token")
                if (response.isSuccessful && response.body()?.success == true) {
                    allPdfs = response.body()?.data ?: emptyList()
                    filterPdfs(binding.etSearch.text?.toString() ?: "")
                } else if (response.code() == 401) {
                    logout()
                } else {
                    showEmpty("Failed to load documents.")
                }
            } catch (e: Exception) {
                showEmpty(getString(R.string.error_network))
            } finally {
                showLoading(false)
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun filterPdfs(query: String) {
        val filtered = if (query.isBlank()) allPdfs
        else allPdfs.filter { it.name.contains(query, ignoreCase = true) }

        adapter.submitList(filtered)

        if (filtered.isEmpty()) {
            binding.emptyState.visibility = View.VISIBLE
            binding.recyclerView.visibility = View.GONE
            binding.tvEmpty.text = if (query.isBlank()) getString(R.string.no_documents)
            else "No results for \"$query\""
        } else {
            binding.emptyState.visibility = View.GONE
            binding.recyclerView.visibility = View.VISIBLE
        }
    }

    private fun openPdf(pdf: PdfFile) {
        val intent = Intent(this, PdfViewerActivity::class.java).apply {
            putExtra(PdfViewerActivity.EXTRA_PDF_ID, pdf.id)
            putExtra(PdfViewerActivity.EXTRA_PDF_NAME, pdf.name)
        }
        startActivity(intent)
    }

    private fun showLoading(loading: Boolean) {
        binding.progressBar.visibility = if (loading) View.VISIBLE else View.GONE
        binding.recyclerView.visibility = if (loading) View.GONE else View.VISIBLE
        binding.emptyState.visibility = View.GONE
    }

    private fun showEmpty(msg: String) {
        binding.emptyState.visibility = View.VISIBLE
        binding.recyclerView.visibility = View.GONE
        binding.tvEmpty.text = msg
    }

    private fun logout() {
        PrefsManager.clearSession(this)
        startActivity(Intent(this, LoginActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        })
        finish()
    }
}
