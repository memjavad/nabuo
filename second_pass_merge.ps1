$results = Get-Content merge_results.txt
$secondPassResults = @()

foreach ($line in $results) {
    if ($line -match "(.*): FAILED") {
        $branch = $Matches[1]
        Write-Host "Second pass merging $branch with -X theirs..."
        $mergeOutput = git merge $branch --no-edit -X theirs 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Successfully merged $branch (theirs)"
            $secondPassResults += "$($branch): SUCCESS (theirs)"
        } else {
            Write-Host "STILL CONFLICT in $branch"
            git merge --abort
            $secondPassResults += "$($branch): STILL_FAILED"
        }
    }
}

$secondPassResults | Out-File -FilePath second_pass_results.txt
Write-Host "Second pass completed. Results saved to second_pass_results.txt"
