import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import {
	SelectControl,
	Spinner,
	TextareaControl,
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';
import ProgressBar from './components/ProgressBar';
import apiFetch from '@wordpress/api-fetch';
import './index.scss';

const BulkTermGenerator = () => {
	const [taxonomies, setTaxonomies] = useState([]);
	const [selectedTaxonomy, setSelectedTaxonomy] = useState('');
	const [terms, setTerms] = useState([]);
	const [isHierarchical, setIsHierarchical] = useState(false);
	const [newTerms, setNewTerms] = useState('');
	const [termCounter, setTermCounter] = useState(0);
	const [queuedTerms, setQueuedTerms] = useState([]);
	const [parentTerm, setParentTerm] = useState('');
	const [loading, setLoading] = useState(false);
	const [editingTerm, setEditingTerm] = useState(null);
	const [deletingTermId, setDeletingTermId] = useState(null);
	const [progress, setProgress] = useState(0);
	const [isGenerating, setIsGenerating] = useState(false);
	const [isBrainstorming, setIsBrainstorming] = useState(false);
	const [notice, setNotice] = useState({ message: '', status: '' });

	const newTermsRef = useRef(null);
	const termTreeContainerRef = useRef(null);

	useEffect(() => {
		// Fetch taxonomies when the component mounts
		apiFetch({ path: '/bulk-term-generator/v1/taxonomies' }).then((data) => {
			const taxonomyOptions = [
				{ label: '', value: '' },
				...Object.keys(data).map((key) => ({
					label: data[key].label,
					value: key,
				})),
			];
			setTaxonomies(taxonomyOptions);
		});

		// Check if taxonomy is provided in URL params
		const params = new URLSearchParams(window.location.search);
		const taxonomyFromUrl = params.get('taxonomy');
		if (taxonomyFromUrl) {
			setSelectedTaxonomy(taxonomyFromUrl);
			fetchTerms(taxonomyFromUrl);
		}

		// Handle browser back and forward button events
		const handlePopState = (event) => {
			const params = new URLSearchParams(window.location.search);
			const taxonomyFromUrl = params.get('taxonomy');
			setSelectedTaxonomy(taxonomyFromUrl);
			if (taxonomyFromUrl) {
				fetchTerms(taxonomyFromUrl);
			} else {
				setTerms([]);
			}
		};

		window.addEventListener('popstate', handlePopState);

		return () => {
			window.removeEventListener('popstate', handlePopState);
		};
	}, []);

	useEffect(() => {
		if (newTermsRef.current && termTreeContainerRef.current) {
			const { top: containerTop } =
				termTreeContainerRef.current.getBoundingClientRect();
			const { top: elementTop } =
				newTermsRef.current.getBoundingClientRect();
			termTreeContainerRef.current.scrollTo({
				top:
					termTreeContainerRef.current.scrollTop +
					elementTop -
					containerTop,
				behavior: 'smooth',
			});
		}
	}, [queuedTerms]);

	const fetchTerms = (taxonomy) => {
		setLoading(true);
		apiFetch({ path: `/bulk-term-generator/v1/taxonomy/${taxonomy}`, parse: false })
			.then((response) => {
				setIsHierarchical(response.headers.get('X-Hierarchical') === 'true');
				return response.json();
			})
			.then((data) => {
				setTerms(data);
				setLoading(false);
			})
			.catch((error) => {
				setNotice({
					message: `Error fetching terms. Please try again.`,
					status: 'error',
				});
				setLoading(false);
			});
	};


	const handleTaxonomyChange = (value) => {
		setSelectedTaxonomy(value);
		fetchTerms(value);
		const url = new URL(window.location);
		url.searchParams.set('taxonomy', value);
		window.history.pushState({ taxonomy: value }, '', url);
	};

	const parseTermLine = (line) => {
		const parts = [];
		let current = '';
		let escaped = false;

		for (let i = 0; i < line.length; i++) {
			const char = line[i];

			if (escaped) {
				current += char;
				escaped = false;
			} else if (char === '\\') {
				escaped = true;
			} else if (char === ',') {
				parts.push(current.trim());
				current = '';
			} else {
				current += char;
			}
		}

		parts.push(current.trim());
		return parts;
	};

	const handleNewTermsChange = (value) => {
		setNewTerms(value);
	};

	const handleEditTerm = (term) => {
		setEditingTerm(term);
	};

	const handleDeleteTerm = (term) => {
		// Count the number of children for the term
		const countChildren = (termId, terms) => {
			const children = terms.filter(t => t.parent === termId);
			let count = children.length;
			children.forEach(child => {
				count += countChildren(child.term_id, terms);
			});
			return count;
		};

		const childCount = countChildren(term.term_id, terms);

		let message;
		if (childCount === 0) {
			message = `Are you sure you want to delete the term "${term.name}"?`;
		} else if (childCount === 1) {
			message = `Are you sure you want to delete the term "${term.name}" and its child?`;
		} else {
			message = `Are you sure you want to delete the term "${term.name}" and its ${childCount} children?`;
		}

		if (!window.confirm(message + '\nThis action cannot be undone!')) {
			return;
		}

		setDeletingTermId(term.term_id);
		setEditingTerm(null);

		if (term.isNew) {
			setQueuedTerms(queuedTerms.filter((t) => t.term_id !== term.term_id));
			setDeletingTermId(null);
		} else {
			apiFetch({
				path: `/bulk-term-generator/v1/terms/${selectedTaxonomy}/${term.term_id}`,
				method: 'DELETE'
			})
				.then(() => {
					setTerms(terms.filter((t) => t.term_id !== term.term_id));
					setNotice({
						message: `"${term.name}" successfully deleted.`,
						status: 'info',
					});
				})
				.catch((error) => {
					setNotice({
						message: `Error deleting term "${term.name}". Please try again.`,
						status: 'error',
					});
				})
				.finally(() => setDeletingTermId(null));
		}
	};

	const handleSaveEdit = () => {
		if (editingTerm.isNew) {
			setQueuedTerms(
				queuedTerms.map((term) =>
					term.term_id === editingTerm.term_id ? editingTerm : term
				)
			);
		} else {
			apiFetch({
				path: `/wp/v2/${selectedTaxonomy}/${editingTerm.term_id}`,
				method: 'POST',
				data: {
					name: editingTerm.name,
					slug: editingTerm.slug,
					description: editingTerm.description,
				},
			}).then(() => {
				setTerms(
					terms.map((term) =>
						term.term_id === editingTerm.term_id ? editingTerm : term
					)
				);
			});
		}
		setNotice({
			message: `"${editingTerm.name}" successfully edited.`,
			status: 'info',
		});
		setEditingTerm(null);
	};

	const renderTermTree = (terms) => {
		const termMap = {};
		terms.forEach((term) => {
			termMap[term.term_id] = { ...term, children: [] };
		});
		terms.forEach((term) => {
			if (term.parent && termMap[term.parent]) {
				termMap[term.parent].children.push(termMap[term.term_id]);
			}
		});
		return Object.values(termMap).filter((term) => term.parent === 0);
	};

	const renderTerms = (terms) => {
		const sortedTerms = [...terms].sort((a, b) => a.name.localeCompare(b.name));

		return (
			<ul className="term-tree">
				{sortedTerms.map((term) => (
					<li
						key={term.term_id}
						className={term.isNew ? 'new-term' : ''}
						ref={term.isNew ? newTermsRef : null}
					>
						<div className="term-content">
                        <span
	                        className="term-name"
	                        onClick={() => handleEditTerm(term)}
                        >
                            {term.name}
                        </span>
							<Button
								icon="edit"
								onClick={() => handleEditTerm(term)}
								className="edit-button"
								disabled={deletingTermId === term.term_id}
							/>
							<Button
								icon="trash"
								onClick={() => handleDeleteTerm(term)}
								className="delete-button"
								disabled={deletingTermId === term.term_id}
							/>
						</div>
						{term.children.length > 0 && renderTerms(term.children)}
					</li>
				))}
			</ul>
		);
	};

	const generateParentOptions = (terms, level = 0) => {
		let options = [];
		terms = terms.sort((a, b) => a.name.localeCompare(b.name));

		terms.forEach((term) => {
			options.push({
				label: `${'—'.repeat(level)} ${term.name}`,
				value: term.term_id,
			});
			if (term.children.length > 0) {
				options = options.concat(
					generateParentOptions(term.children, level + 1)
				);
			}
		});
		return options;
	};

	const handleAddTermsToQueue = () => {
		const termsArray = newTerms
			.split('\n')
			.filter((term) => term.trim())
			.map((line) => {
				const [name, slug, description] = parseTermLine(line);
				return { name, slug, description };
			});

		const newQueuedTerms = termsArray.map((term, index) => {
			const term_id = `new-${termCounter + index}`;
			return {
				term_id,
				name: term.name,
				slug: term.slug,
				description: term.description,
				parent: parentTerm ? parentTerm : 0,
				children: [],
				isNew: true,
			};
		});

		setTermCounter(prevCounter => prevCounter + termsArray.length);

		setQueuedTerms((prevQueuedTerms) => {
			// Filter out any terms that already exist with the same name and parent
			const filteredNewTerms = newQueuedTerms.filter(newTerm => {
				const existingTerm = prevQueuedTerms.concat(terms).find(
					term => term.name === newTerm.name && term.parent === newTerm.parent
				);
				return !existingTerm;
			});

			const message = filteredNewTerms.length === 1
				? '1 term added to the queue! Click "Generate Terms" to create them.'
				: `${filteredNewTerms.length} terms added to the queue! Click "Generate Terms" to create them.`;

			if (filteredNewTerms.length > 0) {
				setNotice({
					message: message,
					status: 'info',
				});
			} else {
				setNotice({
					message: 'No new terms added to the queue because they already exist.',
					status: 'warning',
				});
			}

			return [...prevQueuedTerms, ...filteredNewTerms];
		});

		setNewTerms('');
		setParentTerm('');
	};

	const handleBrainstorming = () => {
		if ( isBrainstorming ) {
			return;
		}
		setIsBrainstorming(true);
	}

	const processTerms = async (terms) => {
		console.log('Processing terms', terms);
		const response = await apiFetch({
			path: '/bulk-term-generator/v1/terms/' + selectedTaxonomy,
			method: 'POST',
			data: { terms },
		});

		const processedTerms = response.processed;
		const idMap = processedTerms.reduce((map, term) => {
			map[term.old_id] = term.term_id;
			return map;
		}, {});

		return { processedTerms, idMap };
	};

	const generateTerms = async () => {
		setIsGenerating(true);
		setProgress(0);

		const batchSize = 10;
		const totalTerms = queuedTerms.length;
		let processedTerms = [];
		let termsToProcess = queuedTerms.slice();
		let idMap = {}; // Map of temporary IDs to new IDs

		while (termsToProcess.length > 0) {
			const currentBatch = termsToProcess.splice(0, batchSize);

			// Update the parent IDs in the current batch using the ID map
			currentBatch.forEach((term) => {
				if (term.parent && idMap[term.parent]) {
					console.log('Updating parent ID', term.parent, idMap[term.parent]);
					term.parent = idMap[term.parent];
				}
			});

			try {
				const { processedTerms: processedBatch, idMap: batchIdMap } = await processTerms(currentBatch);
				processedTerms = [...processedTerms, ...processedBatch];
				idMap = { ...idMap, ...batchIdMap };

				// Update progress
				setProgress(Math.round((processedTerms.length / totalTerms) * 100));

				// Update terms with new IDs
				setTerms((prevTerms) => [
					...prevTerms,
					...currentBatch.map((term) => {
						const processedTerm = processedBatch.find((pt) => pt.old_id === term.term_id);
						return { ...term, term_id: processedTerm.term_id, isNew: false, parent: processedTerm.parent, slug: processedTerm.slug, description: processedTerm.description};
					}),
				]);

				// Remove the terms from the queue
				setQueuedTerms((prevQueuedTerms) =>
					prevQueuedTerms.filter((term) => !currentBatch.includes(term))
				);
			} catch (error) {
				setNotice({
					message: `Error generating terms. Please try again.`,
					status: 'error',
				});
			}
		}

		setIsGenerating(false);
		setNotice({
			message: `${processedTerms.length} terms successfully generated!`,
			status: 'success',
		});
	};

	const combinedTerms = [...terms, ...queuedTerms];

	return (
		<div className="bulk-term-generator">
			{notice.message && (
				<Notice
					status={notice.status}
					isDismissible={true}
					onRemove={() => setNotice({ message: '', status: '' })}
				>
					{notice.message}
				</Notice>
			)}
			{!selectedTaxonomy ? (
				<SelectControl
					label="Select Taxonomy"
					value={selectedTaxonomy}
					options={taxonomies}
					onChange={handleTaxonomyChange}
					className="taxonomy-select"
				/>
			) : (
				<>
					<div className="form-and-tree-container">
						{!editingTerm && (
							<div className="add-term-form">
								<h2>Add Terms</h2>
								{isGenerating && (
									<div className="progress-bar-wrapper">
										<ProgressBar value={progress} />
										<div className="progress-bar-label">
											{progress}
											<span>%</span>
										</div>
									</div>
								)}
								<div className="add-terms-wrapper">
									<TextareaControl
										label="Add Terms (name, slug, description) - one per line"
										value={newTerms}
										onChange={handleNewTermsChange}
										className="add-terms-textarea"
										disabled={isGenerating || isBrainstorming}
									/>
									<Button
										isPrimary
										onClick={handleBrainstorming}
										className="ai-generator"
									>
										<span className="ai-icon">
											{isBrainstorming && (
												<Spinner />
											)}
											{!isBrainstorming && (
												<svg
													xmlns="http://www.w3.org/2000/svg"
													viewBox="0 0 16 16"
													fill="currentColor"
													width="16px"
													height="16px"
												>
													<path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828l.645-1.937zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.734 1.734 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.734 1.734 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.734 1.734 0 0 0 3.407 2.31l.387-1.162zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L10.863.1z"/>
												</svg>
											)}
										</span>
										{isBrainstorming ? <span className="brainstorming">Brainstorming</span> : 'Brainstorm'}
									</Button>
								</div>
								{ isHierarchical && (
									<SelectControl
										label="Parent"
										value={parentTerm}
										options={[
											{label: 'None', value: ''},
											...generateParentOptions(
												renderTermTree(combinedTerms)
											),
										]}
										onChange={(value) => setParentTerm(value)}
										className="parent-select"
									/>
								)}
								<Button
									isSecondary
									onClick={handleAddTermsToQueue}
									className="add-terms-button"
									disabled={!newTerms.trim() || isGenerating || isBrainstorming}
								>
									Add Terms to Queue
								</Button>
								<Button
									isPrimary
									onClick={generateTerms}
									className="generate-terms-button"
									disabled={queuedTerms.length === 0 || isGenerating || isBrainstorming}
								>
									Generate Terms
								</Button>
							</div>
						)}
						{editingTerm && (
							<div className="edit-term-form">
								<h2>Edit Term</h2>
								<TextControl
									label="Name"
									value={editingTerm.name}
									onChange={(value) =>
										setEditingTerm({
											...editingTerm,
											name: value,
										})
									}
								/>
								<TextControl
									label="Slug"
									value={editingTerm.slug}
									onChange={(value) =>
										setEditingTerm({
											...editingTerm,
											slug: value,
										})
									}
								/>
								<TextControl
									label="Description"
									value={editingTerm.description}
									onChange={(value) =>
										setEditingTerm({
											...editingTerm,
											description: value,
										})
									}
								/>
								<Button isPrimary onClick={handleSaveEdit}>
									Save
								</Button>
								<Button onClick={() => setEditingTerm(null)}>
									Cancel
								</Button>
							</div>
						)}
						<div
							className="term-tree-container"
							ref={termTreeContainerRef}
						>
							{loading ? (
								<Spinner />
							) : (
								<>
									<p className="taxonomy-name">
										{
											taxonomies.find(
												(tax) =>
													tax.value ===
													selectedTaxonomy
											).label
										}
									</p>
									{renderTerms(renderTermTree(combinedTerms))}
								</>
							)}
						</div>
					</div>
				</>
			)}
		</div>
	);
};

document.addEventListener('DOMContentLoaded', () => {
	const rootElement = document.getElementById('bulk-term-generator-root');
	if (rootElement) {
		const root = createRoot(rootElement);
		root.render(<BulkTermGenerator />);
	}
});
