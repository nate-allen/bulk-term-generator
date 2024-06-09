import React, { forwardRef } from 'react';
import { __ } from '@wordpress/i18n';
import './ProgressBar.scss';

function UnforwardedProgressBar(props, ref) {
	const { className, value, ...progressProps } = props;
	const isIndeterminate = !Number.isFinite(value);

	return (
		<div className={`progress-bar-track ${className}`}>
			<div
				className={`progress-bar-indicator ${isIndeterminate ? 'indeterminate' : 'determinate'}`}
				style={{
					width: !isIndeterminate ? `${value}%` : undefined,
				}}
			/>
			<progress
				max={100}
				value={value}
				aria-label={__('Loading …')}
				ref={ref}
				className="progress-bar-element"
				{...progressProps}
			/>
		</div>
	);
}

export const ProgressBar = forwardRef(UnforwardedProgressBar);

export default ProgressBar;
