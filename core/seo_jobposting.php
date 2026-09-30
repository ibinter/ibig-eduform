<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"JobPosting",
  "title":"<?= addslashes($o['titre']); ?>",
  "description":"<?= addslashes(strip_tags($o['description'])); ?>",
  "datePosted":"<?= date('Y-m-d',strtotime($o['publie_le'])); ?>",
  "employmentType":"<?= $o['type_contrat']; ?>",
  "hiringOrganization":{
    "@type":"Organization",
    "name":"<?= addslashes($o['nom_legal']); ?>"
  },
  "jobLocation":{
    "@type":"Place",
    "address":{
      "@type":"PostalAddress",
      "addressLocality":"<?= addslashes($o['ville']); ?>",
      "addressCountry":"<?= addslashes($o['pays']); ?>"
    }
  }
}
</script>
