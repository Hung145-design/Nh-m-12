# Create your models here.
from django.db import models

class TranslationHistory(models.Model):
    source_text = models.TextField()
    translated_text = models.TextField()
    target_lang = models.CharField(max_length=10)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.source_text[:30]}... -> {self.target_lang}"
