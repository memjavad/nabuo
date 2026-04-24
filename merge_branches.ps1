$branches = Get-Content unmerged_branches.txt
$results = @()

foreach ($branch in $branches) {
    $branch = $branch.Trim()
    if ($branch -match "^origin/") {
        Write-Host "Merging $branch..."
        $mergeOutput = git merge $branch --no-edit 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Successfully merged $branch"
            $results += "$($branch): SUCCESS"
        } else {
            Write-Host "Conflict or error merging $branch"
            git merge --abort
            $results += "$($branch): FAILED"
        }
    }
}

$results | Out-File -FilePath merge_results.txt
Write-Host "Merge operation completed. Results saved to merge_results.txt"
