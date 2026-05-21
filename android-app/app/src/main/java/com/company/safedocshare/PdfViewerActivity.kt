package com.company.safedocshare

import android.os.Bundle
import android.view.View
import androidx.lifecycle.lifecycleScope
import com.company.safedocshare.api.ApiClient
import com.company.safedocshare.databinding.ActivityPdfViewerBinding
import com.company.safedocshare.utils.PrefsManager
import com.company.safedocshare.utils.SecureActivity
import com.github.barteksc.pdfviewer.scroll.DefaultScrollHandle
import com.github.barteksc.pdfviewer.util.FitPolicy
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class PdfViewerActivity : SecureActivity() {

    companion object {
        const val EXTRA_PDF_ID   = "pdf_id"
        const val EXTRA_PDF_NAME = "pdf_name"
    }

    private lateinit var binding: ActivityPdfViewerBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPdfViewerBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val pdfId   = intent.getIntExtra(EXTRA_PDF_ID, -1)
        val pdfName = intent.getStringExtra(EXTRA_PDF_NAME) ?: "Document"

        setSupportActionBar(binding.toolbar)
        supportActionBar?.title = pdfName
        binding.toolbar.setNavigationOnClickListener { finish() }

        if (pdfId == -1) {
            showError()
            return
        }

        loadPdf(pdfId)
    }

    private fun loadPdf(pdfId: Int) {
        val token = PrefsManager.getToken(this) ?: run { finish(); return }

        binding.loadingLayout.visibility = View.VISIBLE
        binding.errorLayout.visibility   = View.GONE
        binding.pdfView.visibility       = View.GONE

        lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.service.downloadPdf("Bearer $token", pdfId)
                }

                if (response.isSuccessful) {
                    val bytes = withContext(Dispatchers.IO) {
                        response.body()?.bytes()
                    }

                    if (bytes != null) {
                        displayPdf(bytes)
                    } else {
                        showError()
                    }
                } else {
                    showError()
                }
            } catch (e: Exception) {
                showError()
            }
        }
    }

    private fun displayPdf(bytes: ByteArray) {
        binding.loadingLayout.visibility = View.GONE
        binding.pdfView.visibility       = View.VISIBLE

        binding.pdfView
            .fromBytes(bytes)
            .enableSwipe(true)
            .swipeHorizontal(false)
            .enableDoubletap(true)
            .defaultPage(0)
            .enableAnnotationRendering(false)
            .scrollHandle(DefaultScrollHandle(this))
            .spacing(0)
            .pageFitPolicy(FitPolicy.WIDTH)
            .nightMode(false)
            // PDF renderer renders pages as bitmaps – text selection is inherently unavailable
            .onError { showError() }
            .load()
    }

    private fun showError() {
        binding.loadingLayout.visibility = View.GONE
        binding.pdfView.visibility       = View.GONE
        binding.errorLayout.visibility   = View.VISIBLE
    }
}
